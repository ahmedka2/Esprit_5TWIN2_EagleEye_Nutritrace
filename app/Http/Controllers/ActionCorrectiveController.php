<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreActionCorrectiveRequest;
use App\Http\Requests\UpdateActionCorrectiveRequest;
use App\Models\ActionCorrective;
use App\Models\SignalementQualite;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActionCorrectiveController extends Controller
{
    /**
     * Enregistre une nouvelle action corrective sur un signalement qualité et pilote la machine à états.
     */
    public function store(StoreActionCorrectiveRequest $request, SignalementQualite $signalementQualite): RedirectResponse
    {
        $this->authorize('createActionCorrective', $signalementQualite);

        $validated = $request->validated();

        $action = $signalementQualite->actionsCorrectives()->create([
            'responsable_id' => $request->user()->id,
            'type_decision' => $validated['type_decision'],
            'mesure_appliquee' => trim($validated['mesure_appliquee']),
            'rapport_intervention' => trim($validated['rapport_intervention']),
            'statut_action' => $validated['statut_action'],
            'date_application' => $validated['date_application'],
        ]);

        $this->synchroniserStatutSignalement($signalementQualite, $action);

        return redirect()
            ->route('signalements-qualite.show', $signalementQualite)
            ->with('status', 'La mesure corrective a été enregistrée et le statut du signalement a été actualisé.');
    }

    /**
     * Formulaire d'édition d'une action corrective existante.
     */
    public function edit(Request $request, SignalementQualite $signalementQualite, ActionCorrective $actionCorrective): View
    {
        if ($actionCorrective->signalement_qualite_id !== $signalementQualite->id) {
            abort(404);
        }

        $this->authorize('updateActionCorrective', [$signalementQualite, $actionCorrective]);

        return view('signalements_qualite.edit_action', [
            'signalement' => $signalementQualite->load('produitFinal'),
            'action' => $actionCorrective,
            'layout' => $this->resolveLayout($request->user()),
        ]);
    }

    /**
     * Met à jour une action corrective et synchronise l'état du signalement parent.
     */
    public function update(
        UpdateActionCorrectiveRequest $request,
        SignalementQualite $signalementQualite,
        ActionCorrective $actionCorrective
    ): RedirectResponse {
        if ($actionCorrective->signalement_qualite_id !== $signalementQualite->id) {
            abort(404);
        }

        $this->authorize('updateActionCorrective', [$signalementQualite, $actionCorrective]);

        $validated = $request->validated();

        $actionCorrective->update([
            'type_decision' => $validated['type_decision'],
            'mesure_appliquee' => trim($validated['mesure_appliquee']),
            'rapport_intervention' => trim($validated['rapport_intervention']),
            'statut_action' => $validated['statut_action'],
            'date_application' => $validated['date_application'],
        ]);

        $this->synchroniserStatutSignalement($signalementQualite, $actionCorrective);

        return redirect()
            ->route('signalements-qualite.show', $signalementQualite)
            ->with('status', 'L\'action corrective a été mise à jour avec succès.');
    }

    /**
     * Supprime une action corrective.
     */
    public function destroy(
        Request $request,
        SignalementQualite $signalementQualite,
        ActionCorrective $actionCorrective
    ): RedirectResponse {
        if ($actionCorrective->signalement_qualite_id !== $signalementQualite->id) {
            abort(404);
        }

        $this->authorize('deleteActionCorrective', [$signalementQualite, $actionCorrective]);

        $actionCorrective->delete();

        return redirect()
            ->route('signalements-qualite.show', $signalementQualite)
            ->with('status', 'L\'action corrective a été supprimée.');
    }

    /**
     * Automatisation métier (Machine à états) :
     * - Si l'action corrective est 'terminee', le signalement passe automatiquement à 'resolu'
     *   (ou 'rejete' si type_decision === 'non_concluant').
     * - Sinon, si le signalement était 'ouvert', il passe automatiquement à 'en_analyse'.
     */
    private function synchroniserStatutSignalement(SignalementQualite $signalement, ActionCorrective $action): void
    {
        if ($action->statut_action === 'terminee') {
            $nouveauStatut = $action->type_decision === 'non_concluant' ? 'rejete' : 'resolu';
            $signalement->update(['statut' => $nouveauStatut]);

            return;
        }

        if ($signalement->statut === 'ouvert') {
            $signalement->update(['statut' => 'en_analyse']);
        }
    }

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
}
