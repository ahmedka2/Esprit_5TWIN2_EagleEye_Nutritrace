<?php

namespace App\Http\Controllers;

use App\Services\NutriTraceAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiAssistantController extends Controller
{
    public function __construct(
        private readonly NutriTraceAiService $aiService
    ) {}

    /**
     * POST /ai/analyze-signalement
     * Analyse sémantique (Xenova/multilingual-e5-small) et visuelle (Xenova/vit-base-patch16-224)
     * d'un signalement qualité en cours de saisie.
     */
    public function analyzeSignalement(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'description' => ['required', 'string', 'min:3', 'max:2000'],
            'vision_label' => ['nullable', 'string', 'max:255'],
            'photo_filename' => ['nullable', 'string', 'max:255'],
        ], [
            'description.required' => 'Veuillez saisir une description pour lancer le diagnostic IA.',
            'description.min' => 'Veuillez saisir au moins quelques mots pour permettre l\'analyse IA.',
        ]);

        $result = $this->aiService->analyzeSignalement(
            $validated['description'],
            $validated['vision_label'] ?? null,
            $validated['photo_filename'] ?? null
        );

        return response()->json([
            'success' => true,
            'data' => $result,
            'model_id' => $result['model_id'],
            'vision_model_id' => NutriTraceAiService::MODEL_VISION,
            'embeddings_model_id' => NutriTraceAiService::MODEL_EMBEDDINGS,
            'model_origin' => $result['model_origin'],
            'confidence_score' => $result['confidence_score'],
        ]);
    }

    /**
     * POST /ai/suggest-replies
     * Suggère les 2 meilleures Réponses Rapides par Similarité Cosinus (Xenova/multilingual-e5-small)
     * et analyse le sentiment/urgence du message via Xenova/distilbert-base-multilingual-cased-sentiments-student.
     */
    public function suggestReplies(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ], [
            'message.required' => 'Veuillez fournir un message à analyser.',
        ]);

        $reponsesRapides = $request->user()->reponsesRapides()->orderBy('titre')->get();
        $replyAnalysis = $this->aiService->suggestQuickReplies($validated['message'], $reponsesRapides, 2);
        $sentimentAnalysis = $this->aiService->analyzeMessageSentiment($validated['message']);

        return response()->json([
            'success' => true,
            'suggestions' => $replyAnalysis['suggestions'],
            'sentiment' => $sentimentAnalysis,
            'model_id' => NutriTraceAiService::MODEL_EMBEDDINGS,
            'sentiment_model_id' => NutriTraceAiService::MODEL_SENTIMENT,
            'model_origin' => $replyAnalysis['model_origin'],
            'confidence_score' => $replyAnalysis['confidence_score'],
        ]);
    }

    /**
     * POST /ai/analyze-note
     * Prédit automatiquement la note de 1 à 5 étoiles à partir d'un avis personnel
     * via Xenova/distilbert-base-multilingual-cased-sentiments-student.
     */
    public function analyzeNote(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'note' => ['required', 'string', 'min:2', 'max:1000'],
        ], [
            'note.required' => 'Veuillez saisir une note personnelle à analyser.',
        ]);

        $prediction = $this->aiService->predictNoteStars($validated['note']);

        return response()->json([
            'success' => true,
            'rating' => $prediction['rating'],
            'stars' => $prediction['stars'],
            'sentiment' => $prediction['sentiment'],
            'label' => $prediction['label'],
            'model_id' => $prediction['model_id'],
            'model_origin' => $prediction['model_origin'],
            'confidence_score' => $prediction['confidence_score'],
        ]);
    }
}
