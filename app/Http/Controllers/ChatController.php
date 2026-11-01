<?php

namespace App\Http\Controllers;

use App\Models\ConversationArchive;
use App\Models\Message;
use App\Models\ProduitFinal;
use App\Models\User;
use App\Services\NutriTraceAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ChatController extends Controller
{
    /**
     * Display all conversations for the authenticated user, with active / archived tabs.
     */
    public function index(Request $request): View
    {
        $userId = $request->user()->id;
        $viewFilter = $request->query('view', 'actives'); // 'actives' or 'archives'

        // Retrieve all archived conversation keys for current user
        $archivedKeys = ConversationArchive::where('user_id', $userId)
            ->get()
            ->mapWithKeys(fn ($a) => [$a->produit_final_id . '-' . $a->interlocutor_id => true])
            ->all();

        // Retrieve all messages involving the current user
        $messages = Message::with(['produitFinal', 'sender', 'receiver'])
            ->where(function ($q) use ($userId) {
                $q->where('sender_id', $userId)
                  ->orWhere('receiver_id', $userId);
            })
            ->latest()
            ->get();

        // Group into unique conversations by product and the other interlocutor
        $allConversations = $messages->groupBy(function (Message $m) use ($userId) {
            $otherId = ($m->sender_id === $userId) ? $m->receiver_id : $m->sender_id;
            return $m->produit_final_id . '-' . $otherId;
        })->map(function ($thread, $key) use ($userId, $archivedKeys) {
            $latest = $thread->first();
            $otherUser = ($latest->sender_id === $userId) ? $latest->receiver : $latest->sender;
            $unreadCount = $thread->where('receiver_id', $userId)->where('is_read', false)->count();
            $isArchived = isset($archivedKeys[$key]);

            return (object) [
                'produit' => $latest->produitFinal,
                'otherUser' => $otherUser,
                'latestMessage' => $latest,
                'unreadCount' => $unreadCount,
                'totalMessages' => $thread->count(),
                'isArchived' => $isArchived,
                'key' => $key,
            ];
        });

        $activeConversations = $allConversations->where('isArchived', false);
        $archivedConversations = $allConversations->where('isArchived', true);

        $conversations = ($viewFilter === 'archives') ? $archivedConversations : $activeConversations;

        return view('chat.index', [
            'conversations' => $conversations,
            'activeCount' => $activeConversations->count(),
            'archiveCount' => $archivedConversations->count(),
            'currentView' => $viewFilter,
            'layout' => $this->resolveLayout($request->user()),
        ]);
    }

    /**
     * Show a conversation thread between the authenticated user and another user for a specific product.
     */
    public function show(Request $request, ProduitFinal $produitFinal, User $interlocutor, NutriTraceAiService $aiService): View
    {
        $authUser = $request->user();

        // Mark incoming messages as read
        Message::where('produit_final_id', $produitFinal->id)
            ->where('sender_id', $interlocutor->id)
            ->where('receiver_id', $authUser->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $messages = Message::betweenUsersForProduct($authUser->id, $interlocutor->id, $produitFinal->id)
            ->with(['sender', 'receiver'])
            ->get();

        // Producer quick replies if the user is a pro
        $reponsesRapides = collect();
        $smartReplies = [];

        if ($authUser->isPro()) {
            $reponsesRapides = $authUser->reponsesRapides()->orderBy('titre')->get();

            // Smart Reply IA (Similarité Cosinus — Xenova/multilingual-e5-small)
            if ($reponsesRapides->isNotEmpty()) {
                $lastIncoming = $messages->where('sender_id', $interlocutor->id)->where('deleted_by_sender', false)->last();
                $contextText = $lastIncoming?->message ?? ($produitFinal->nom . ' bio origine magasin');
                $smartAnalysis = $aiService->suggestQuickReplies($contextText, $reponsesRapides, 2);
                $smartReplies = $smartAnalysis['suggestions'];
            }
        }

        // Predefined contact topics / motifs
        $motifs = Message::MOTIFS;

        $isArchived = ConversationArchive::where('user_id', $authUser->id)
            ->where('produit_final_id', $produitFinal->id)
            ->where('interlocutor_id', $interlocutor->id)
            ->exists();

        return view('chat.show', [
            'produit' => $produitFinal,
            'interlocutor' => $interlocutor,
            'messages' => $messages,
            'reponsesRapides' => $reponsesRapides,
            'smartReplies' => $smartReplies,
            'motifs' => $motifs,
            'isArchived' => $isArchived,
            'layout' => $this->resolveLayout($authUser),
        ]);
    }

    /**
     * Store and send a new message with optional predefined motif and DistilBERT sentiment analysis.
     */
    public function store(Request $request, ProduitFinal $produitFinal, NutriTraceAiService $aiService): JsonResponse|RedirectResponse
    {
        $authUser = $request->user();

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'receiver_id' => ['nullable', 'integer', 'exists:users,id'],
            'motif' => ['nullable', 'string', Rule::in(array_keys(Message::MOTIFS))],
        ], [
            'message.required' => 'Veuillez saisir votre message.',
            'message.max' => 'Le message ne doit pas dépasser 2000 caractères.',
        ]);

        $creator = $produitFinal->creator;

        // Determine who the receiver is
        if ($authUser->id === $creator?->id) {
            // The creator is replying to a consumer
            $receiverId = $validated['receiver_id'] ?? null;
            if (!$receiverId) {
                return back()->with('error', 'Destinataire non spécifié pour la réponse.');
            }
        } else {
            // A consumer is writing to the product creator
            if (!$creator) {
                return back()->with('error', 'Le producteur de ce lot n\'est pas disponible pour le moment.');
            }
            $receiverId = $creator->id;
        }

        if ($authUser->id === $receiverId) {
            return back()->with('error', 'Vous ne pouvez pas vous envoyer de message à vous-même.');
        }

        $cleanText = trim($validated['message']);
        $sentimentResult = $aiService->analyzeMessageSentiment($cleanText);

        $message = Message::create([
            'produit_final_id' => $produitFinal->id,
            'sender_id' => $authUser->id,
            'receiver_id' => $receiverId,
            'message' => $cleanText,
            'motif' => $validated['motif'] ?? null,
            'is_read' => false,
            'deleted_by_sender' => false,
            'ai_sentiment' => $sentimentResult['ai_sentiment'],
            'ai_urgence_score' => $sentimentResult['ai_urgence_score'],
            'ai_model_used' => $sentimentResult['ai_model_used'],
        ]);

        $message->load('sender');

        $motifLabel = $message->motif ? (Message::MOTIFS[$message->motif] ?? null) : null;
        $aiBadge = $message->ai_sentiment_badge;

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Message envoyé avec succès.',
                'data' => [
                    'id' => $message->id,
                    'text' => $message->message,
                    'motif' => $message->motif,
                    'motif_label' => $motifLabel,
                    'sender_name' => 'Vous',
                    'sender_role' => $message->sender->role_label,
                    'created_at' => $message->created_at->format('H:i'),
                    'created_at_human' => $message->created_at->diffForHumans(),
                    'is_mine' => true,
                    'ai_sentiment' => $message->ai_sentiment,
                    'ai_urgence_score' => $message->ai_urgence_score,
                    'ai_model_used' => $message->ai_model_used,
                    'ai_badge' => $aiBadge,
                ],
            ]);
        }

        return back()->with('status', 'Votre message a été transmis avec succès.');
    }

    /**
     * Poll for incoming real-time messages between users for a product without page reloads.
     */
    public function poll(Request $request, ProduitFinal $produitFinal, User $interlocutor): JsonResponse
    {
        $authUser = $request->user();
        $lastId = (int) $request->query('last_id', 0);

        $newMessages = Message::betweenUsersForProduct($authUser->id, $interlocutor->id, $produitFinal->id)
            ->where('id', '>', $lastId)
            ->with(['sender'])
            ->get();

        if ($newMessages->isNotEmpty()) {
            Message::where('produit_final_id', $produitFinal->id)
                ->where('sender_id', $interlocutor->id)
                ->where('receiver_id', $authUser->id)
                ->where('is_read', false)
                ->update(['is_read' => true]);
        }

        $formatted = $newMessages->map(function (Message $m) use ($authUser) {
            $isMine = ($m->sender_id === $authUser->id);
            $motifLabel = $m->motif ? (Message::MOTIFS[$m->motif] ?? null) : null;

            return [
                'id' => $m->id,
                'message' => $m->deleted_by_sender ? 'Ce message a été supprimé par l\'expéditeur.' : $m->message,
                'is_deleted' => (bool) $m->deleted_by_sender,
                'motif' => $m->motif,
                'motif_label' => $motifLabel,
                'sender_name' => $isMine ? 'Vous' : $m->sender->name . ' (' . $m->sender->role_label . ')',
                'time' => $m->created_at->format('H:i'),
                'is_mine' => $isMine,
                'ai_sentiment' => $m->ai_sentiment,
                'ai_urgence_score' => $m->ai_urgence_score,
                'ai_model_used' => $m->ai_model_used,
                'ai_badge' => $m->ai_sentiment_badge,
            ];
        });

        return response()->json([
            'success' => true,
            'messages' => $formatted,
            'last_id' => $newMessages->max('id') ?? $lastId,
        ]);
    }

    /**
     * Delete a single message sent by the authenticated user.
     */
    public function destroy(Request $request, Message $message): JsonResponse|RedirectResponse
    {
        if ($message->sender_id !== $request->user()->id) {
            abort(403, 'Action non autorisée.');
        }

        $message->update(['deleted_by_sender' => true]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Message supprimé.',
                'id' => $message->id,
            ]);
        }

        return back()->with('status', 'Message supprimé avec succès.');
    }

    /**
     * Toggle archive status for a conversation thread.
     */
    public function toggleArchive(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'produit_final_id' => ['required', 'integer', 'exists:produits_finaux,id'],
            'interlocutor_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $userId = $request->user()->id;

        $existing = ConversationArchive::where('user_id', $userId)
            ->where('produit_final_id', $validated['produit_final_id'])
            ->where('interlocutor_id', $validated['interlocutor_id'])
            ->first();

        if ($existing) {
            $existing->delete();
            $archived = false;
            $msg = 'Conversation désarchivée.';
        } else {
            ConversationArchive::create([
                'user_id' => $userId,
                'produit_final_id' => $validated['produit_final_id'],
                'interlocutor_id' => $validated['interlocutor_id'],
            ]);
            $archived = true;
            $msg = 'Conversation archivée.';
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'archived' => $archived,
                'message' => $msg,
            ]);
        }

        return back()->with('status', $msg);
    }

    /**
     * Resolve the appropriate layout depending on the user's role.
     */
    private function resolveLayout(?User $user): string
    {
        if (! $user) {
            return 'layouts.front';
        }

        if ($user->isAdmin() || $user->isTransformateur() || $user->isDistributeur()) {
            return 'layouts.admin';
        }

        if ($user->isProducteur()) {
            return 'layouts.producteur';
        }

        return 'layouts.front';
    }
}
