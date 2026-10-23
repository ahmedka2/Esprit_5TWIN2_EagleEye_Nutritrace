<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSignalementQualiteRequest;
use App\Http\Requests\UpdateSignalementQualiteRequest;
use App\Models\ProduitFinal;
use App\Models\SignalementQualite;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SignalementQualiteController extends Controller
{
    /**
     * Liste filtrable des signalements qualité avec indicateurs statistiques selon le rôle.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', SignalementQualite::class);

        $user = $request->user();
        $baseQuery = $this->scopedQueryForUser($user);

        // Statistiques globales sur le périmètre de l'utilisateur
        $totalSignalements = (clone $baseQuery)->count();
        $critiquesEnAttente = (clone $baseQuery)
            ->where('gravite', 'critique')
            ->whereIn('statut', ['ouvert', 'en_analyse'])
            ->count();
        $resolusCount = (clone $baseQuery)
            ->where('statut', 'resolu')
            ->count();
        $tauxResolution = $totalSignalements > 0
            ? round(($resolusCount / $totalSignalements) * 100, 1)
            : 0.0;

        // Application des filtres de recherche
        $filteredQuery = (clone $baseQuery)
            ->with([
                'user',
                'produitFinal.transformation.matierePremiere.producteur',
                'produitFinal.user',
                'actionsCorrectives',
            ])
            ->withCount('actionsCorrectives');

        if ($request->filled('statut') && array_key_exists($request->string('statut')->toString(), SignalementQualite::STATUTS)) {
            $filteredQuery->where('statut', $request->string('statut')->toString());
        }

        if ($request->filled('gravite') && array_key_exists($request->string('gravite')->toString(), SignalementQualite::GRAVITES)) {
            $filteredQuery->where('gravite', $request->string('gravite')->toString());
        }

        if ($request->filled('type_anomalie') && array_key_exists($request->string('type_anomalie')->toString(), SignalementQualite::TYPES_ANOMALIE)) {
            $filteredQuery->where('type_anomalie', $request->string('type_anomalie')->toString());
        }

        $search = trim($request->string('search', $request->string('q')->toString())->toString());
        if ($search !== '') {
            $filteredQuery->where(function (Builder $q) use ($search) {
                $q->where('reference', 'like', '%' . $search . '%')
                    ->orWhere('code_lot_saisi', 'like', '%' . $search . '%')
                    ->orWhereHas('produitFinal', function (Builder $pq) use ($search) {
                        $pq->where('nom', 'like', '%' . $search . '%')
                            ->orWhere('code', 'like', '%' . $search . '%');
                    });
            });
        }

        $signalements = $filteredQuery
            ->orderByRaw("CASE WHEN gravite = 'critique' AND statut IN ('ouvert', 'en_analyse') THEN 0 ELSE 1 END")
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('signalements_qualite.index', [
            'signalements' => $signalements,
            'totalSignalements' => $totalSignalements,
            'critiquesEnAttente' => $critiquesEnAttente,
            'resolusCount' => $resolusCount,
            'tauxResolution' => $tauxResolution,
            'layout' => $this->resolveLayout($user),
        ]);
    }

    /**
     * Formulaire de création d'un signalement qualité (avec pré-remplissage depuis un lot ou favori).
     */
    public function create(Request $request): View
    {
        $this->authorize('create', SignalementQualite::class);

        $produitsFinaux = ProduitFinal::with('transformation.matierePremiere.producteur')
            ->latest()
            ->get();

        $selectedProduit = null;
        if ($request->filled('produit_final_id')) {
            $selectedProduit = ProduitFinal::find($request->integer('produit_final_id'));
        } elseif ($request->filled('code_lot_saisi') || $request->filled('lot')) {
            $code = trim($request->string('code_lot_saisi', $request->string('lot')->toString())->toString());
            $selectedProduit = ProduitFinal::where('code', $code)->first();
        }

        $prefilledLotCode = $request->query(
            'code_lot_saisi',
            $request->query('lot', $selectedProduit?->code ?? '')
        );

        return view('signalements_qualite.create', [
            'produitsFinaux' => $produitsFinaux,
            'selectedProduit' => $selectedProduit,
            'prefilledLotCode' => $prefilledLotCode,
            'layout' => $this->resolveLayout($request->user()),
        ]);
    }

    /**
     * Enregistre un nouveau signalement qualité.
     */
    public function store(StoreSignalementQualiteRequest $request): RedirectResponse
    {
        $this->authorize('create', SignalementQualite::class);

        $data = $request->validated();

        if ($request->hasFile('photo_preuve')) {
            $data['photo_preuve'] = $this->uploadPhoto($request);
        }

        $signalement = SignalementQualite::create([
            'user_id' => $request->user()->id,
            'produit_final_id' => $data['produit_final_id'],
            'code_lot_saisi' => trim($data['code_lot_saisi']),
            'type_anomalie' => $data['type_anomalie'],
            'gravite' => $data['gravite'],
            'description' => trim($data['description']),
            'lieu_achat' => isset($data['lieu_achat']) ? trim((string) $data['lieu_achat']) : null,
            'date_constat' => $data['date_constat'],
            'photo_preuve' => $data['photo_preuve'] ?? null,
            'statut' => 'ouvert',
        ]);

        return redirect()
            ->route('signalements-qualite.show', $signalement)
            ->with('status', "Votre signalement qualité ({$signalement->reference}) a bien été enregistré et transmis au producteur.");
    }

    /**
     * Affiche le détail d'un signalement qualité, ses actions correctives et le pont vers la messagerie.
     */
    public function show(Request $request, SignalementQualite $signalementQualite): View
    {
        $this->authorize('view', $signalementQualite);

        $signalementQualite->load([
            'user',
            'produitFinal.transformation.matierePremiere.producteur',
            'produitFinal.user',
            'actionsCorrectives.responsable',
        ]);

        $user = $request->user();
        $produit = $signalementQualite->produitFinal;
        $creator = $produit?->creator;

        // Résolution du lien direct vers la messagerie du lot entre le consommateur et le producteur
        $chatUrl = null;
        $chatInterlocutor = null;

        if ($produit) {
            if ($user->id === $signalementQualite->user_id && $creator && $creator->id !== $user->id) {
                $chatInterlocutor = $creator;
                $chatUrl = route('chat.show', [
                    'produitFinal' => $produit->code,
                    'interlocutor' => $creator->id,
                ]);
            } elseif ($user->id !== $signalementQualite->user_id && $signalementQualite->user) {
                $chatInterlocutor = $signalementQualite->user;
                $chatUrl = route('chat.show', [
                    'produitFinal' => $produit->code,
                    'interlocutor' => $signalementQualite->user->id,
                ]);
            }
        }

        return view('signalements_qualite.show', [
            'signalement' => $signalementQualite,
            'produit' => $produit,
            'creator' => $creator,
            'chatUrl' => $chatUrl,
            'chatInterlocutor' => $chatInterlocutor,
            'canUpdate' => $user->can('update', $signalementQualite),
            'canDelete' => $user->can('delete', $signalementQualite),
            'canChangeStatut' => $user->can('changeStatut', $signalementQualite),
            'canCreateAction' => $user->can('createActionCorrective', $signalementQualite),
            'layout' => $this->resolveLayout($user),
        ]);
    }

    /**
     * Formulaire d'édition d'un signalement qualité.
     */
    public function edit(Request $request, SignalementQualite $signalementQualite): View
    {
        $this->authorize('update', $signalementQualite);

        $signalementQualite->load('produitFinal');
        $produitsFinaux = ProduitFinal::latest()->get();

        return view('signalements_qualite.edit', [
            'signalement' => $signalementQualite,
            'produitsFinaux' => $produitsFinaux,
            'layout' => $this->resolveLayout($request->user()),
        ]);
    }

    /**
     * Met à jour un signalement qualité (ou uniquement son statut par le Producteur / Admin).
     */
    public function update(UpdateSignalementQualiteRequest $request, SignalementQualite $signalementQualite): RedirectResponse
    {
        if ($request->isStatusOnlyUpdate()) {
            $this->authorize('changeStatut', $signalementQualite);
            $validated = $request->validated();
            $signalementQualite->update(['statut' => $validated['statut']]);

            return redirect()
                ->route('signalements-qualite.show', $signalementQualite)
                ->with('status', "Le statut du signalement {$signalementQualite->reference} a été mis à jour.");
        }

        $this->authorize('update', $signalementQualite);

        $data = $request->validated();

        if ($request->hasFile('photo_preuve')) {
            $data['photo_preuve'] = $this->uploadPhoto($request);
        } else {
            unset($data['photo_preuve']);
        }

        $signalementQualite->update($data);

        return redirect()
            ->route('signalements-qualite.show', $signalementQualite)
            ->with('status', "Le signalement {$signalementQualite->reference} a été modifié avec succès.");
    }

    /**
     * Endpoint dédié au changement rapide du statut par le Producteur ou l'Admin.
     */
    public function updateStatut(Request $request, SignalementQualite $signalementQualite): RedirectResponse
    {
        $this->authorize('changeStatut', $signalementQualite);

        $validated = $request->validate([
            'statut' => ['required', 'string', Rule::in(array_keys(SignalementQualite::STATUTS))],
        ], [
            'statut.required' => 'Veuillez choisir un statut.',
            'statut.in' => 'Le statut sélectionné est invalide.',
        ]);

        $signalementQualite->update(['statut' => $validated['statut']]);

        return back()->with('status', "Le statut du signalement {$signalementQualite->reference} est passé à « {$signalementQualite->statut_label} ».");
    }

    /**
     * Supprime un signalement qualité.
     */
    public function destroy(SignalementQualite $signalementQualite): RedirectResponse
    {
        $this->authorize('delete', $signalementQualite);

        $ref = $signalementQualite->reference;
        $signalementQualite->delete();

        return redirect()
            ->route('signalements-qualite.index')
            ->with('status', "Le signalement {$ref} a été supprimé.");
    }

    /**
     * Construit la requête de base filtrée selon le rôle de l'utilisateur.
     */
    private function scopedQueryForUser(User $user): Builder
    {
        $query = SignalementQualite::query();

        if ($user->isAdmin()) {
            return $query;
        }

        if ($user->isProducteur()) {
            $userId = $user->id;

            return $query->where(function (Builder $q) use ($userId) {
                $q->where('user_id', $userId)
                    ->orWhereHas('produitFinal', function (Builder $pq) use ($userId) {
                        $pq->where('user_id', $userId)
                            ->orWhereHas('transformation.matierePremiere', function (Builder $mq) use ($userId) {
                                $mq->where('producteur_id', $userId);
                            });
                    });
            });
        }

        return $query->where('user_id', $user->id);
    }

    /**
     * Détermine le layout Blade à utiliser selon le rôle de l'utilisateur connecté.
     */
    private function resolveLayout(User $user): string
    {
        if ($user->isAdmin()) {
            return 'layouts.admin';
        }

        if ($user->isProducteur()) {
            return 'layouts.producteur';
        }

        return 'layouts.front';
    }

    /**
     * Stocke la photo justificative dans public/signalements.
     */
    private function uploadPhoto(Request $request): string
    {
        $file = $request->file('photo_preuve');
        $directory = public_path('signalements');

        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $extension = $file->getClientOriginalExtension() ?: 'jpg';
        $filename = uniqid('sig_', true) . '.' . strtolower($extension);

        $file->move($directory, $filename);

        return 'signalements/' . $filename;
    }
}
