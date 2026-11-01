<?php

namespace App\Http\Controllers;

use App\Models\ReponseRapide;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReponseRapideController extends Controller
{
    /**
     * Display a listing of quick replies for the authenticated producer/user.
     */
    public function index(Request $request): View
    {
        $this->authorizePro($request);

        $reponses = $request->user()->reponsesRapides()->latest()->get();

        return view('reponses_rapides.index', [
            'reponses' => $reponses,
            'layout' => $this->resolveLayout($request->user()),
        ]);
    }

    /**
     * Store a new quick reply.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizePro($request);
        $validated = $request->validate([
            'titre' => ['required', 'string', 'max:100'],
            'contenu' => ['required', 'string', 'max:2000'],
            'raccourci' => ['nullable', 'string', 'max:30'],
        ], [
            'titre.required' => 'Le titre est obligatoire.',
            'contenu.required' => 'Le contenu de la réponse est obligatoire.',
        ]);

        $reponse = $request->user()->reponsesRapides()->create($validated);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Modèle de réponse créé avec succès.',
                'data' => $reponse,
            ]);
        }

        return redirect()->route('reponses-rapides.index')->with('status', 'Modèle de réponse rapide créé avec succès.');
    }

    /**
     * Update an existing quick reply.
     */
    public function update(Request $request, ReponseRapide $reponseRapide): RedirectResponse|JsonResponse
    {
        $this->authorizePro($request);

        if ($reponseRapide->user_id !== $request->user()->id) {
            abort(403, 'Action non autorisée.');
        }

        $validated = $request->validate([
            'titre' => ['required', 'string', 'max:100'],
            'contenu' => ['required', 'string', 'max:2000'],
            'raccourci' => ['nullable', 'string', 'max:30'],
        ]);

        $reponseRapide->update($validated);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Modèle de réponse mis à jour.',
                'data' => $reponseRapide,
            ]);
        }

        return redirect()->route('reponses-rapides.index')->with('status', 'Modèle mis à jour avec succès.');
    }

    /**
     * Remove a quick reply.
     */
    public function destroy(Request $request, ReponseRapide $reponseRapide): RedirectResponse|JsonResponse
    {
        $this->authorizePro($request);

        if ($reponseRapide->user_id !== $request->user()->id) {
            abort(403, 'Action non autorisée.');
        }

        $reponseRapide->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Modèle de réponse supprimé.',
            ]);
        }

        return redirect()->route('reponses-rapides.index')->with('status', 'Modèle de réponse supprimé.');
    }

    /**
     * JSON API to fetch current user's quick replies for the chat dropdown.
     */
    public function apiList(Request $request): JsonResponse
    {
        $this->authorizePro($request);

        $reponses = $request->user()->reponsesRapides()->orderBy('titre')->get(['id', 'titre', 'contenu', 'raccourci']);

        return response()->json([
            'success' => true,
            'reponses' => $reponses,
        ]);
    }

    /**
     * Ensure the authenticated user has professional or administrative privileges.
     */
    private function authorizePro(Request $request): void
    {
        if (! $request->user()?->isPro()) {
            abort(403, 'Accès réservé aux professionnels et administrateurs.');
        }
    }

    /**
     * Resolve the appropriate layout depending on the user's role.
     */
    private function resolveLayout(?\App\Models\User $user): string
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
