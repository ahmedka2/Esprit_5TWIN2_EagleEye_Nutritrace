<?php

namespace App\Policies;

use App\Models\ActionCorrective;
use App\Models\SignalementQualite;
use App\Models\User;

class SignalementQualitePolicy
{
    /**
     * L'administrateur dispose d'un accès complet sur tous les signalements et actions correctives.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    /**
     * Tout utilisateur authentifié peut lister ses signalements pertinents.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Consommateur : voit ses propres signalements.
     * Producteur : voit les signalements portant sur ses lots de produits finaux (ou ceux qu'il a lui-même créés).
     */
    public function view(User $user, SignalementQualite $signalement): bool
    {
        if ($signalement->user_id === $user->id) {
            return true;
        }

        if ($user->isProducteur() && $this->isProducteurOfLot($user, $signalement)) {
            return true;
        }

        return false;
    }

    /**
     * Tout utilisateur authentifié (notamment Consommateur) peut créer un signalement sur un produit final.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Consommateur : peut modifier son propre signalement UNIQUEMENT si statut === 'ouvert'
     * et qu'aucune action corrective n'a encore été ajoutée.
     */
    public function update(User $user, SignalementQualite $signalement): bool
    {
        if ($signalement->user_id === $user->id) {
            return $signalement->peutEtreModifieParConsommateur();
        }

        return false;
    }

    /**
     * Consommateur : peut supprimer son propre signalement UNIQUEMENT si statut === 'ouvert'
     * et qu'aucune action corrective n'a encore été ajoutée.
     */
    public function delete(User $user, SignalementQualite $signalement): bool
    {
        if ($signalement->user_id === $user->id) {
            return $signalement->peutEtreModifieParConsommateur();
        }

        return false;
    }

    /**
     * Producteur du lot (ou Admin via before) : peut changer le statut du signalement.
     */
    public function changeStatut(User $user, SignalementQualite $signalement): bool
    {
        return $user->isProducteur() && $this->isProducteurOfLot($user, $signalement);
    }

    /**
     * Producteur du lot (ou Admin via before) : peut créer une action corrective sur ce signalement.
     */
    public function createActionCorrective(User $user, SignalementQualite $signalement): bool
    {
        return $user->isProducteur() && $this->isProducteurOfLot($user, $signalement);
    }

    /**
     * Producteur du lot : peut modifier ses propres actions correctives sur ces signalements.
     */
    public function updateActionCorrective(User $user, SignalementQualite $signalement, ActionCorrective $action): bool
    {
        return $user->isProducteur()
            && $action->responsable_id === $user->id
            && $this->isProducteurOfLot($user, $signalement);
    }

    /**
     * Producteur du lot : peut supprimer ses propres actions correctives sur ces signalements.
     */
    public function deleteActionCorrective(User $user, SignalementQualite $signalement, ActionCorrective $action): bool
    {
        return $user->isProducteur()
            && $action->responsable_id === $user->id
            && $this->isProducteurOfLot($user, $signalement);
    }

    /**
     * Vérifie si le producteur est responsable du ProduitFinal rattaché au signalement.
     */
    public function isProducteurOfLot(User $user, SignalementQualite $signalement): bool
    {
        $produit = $signalement->produitFinal;
        if (! $produit) {
            return false;
        }

        if ((int) $produit->user_id === (int) $user->id) {
            return true;
        }

        $creator = $produit->creator;
        if ($creator && (int) $creator->id === (int) $user->id) {
            return true;
        }

        $producteurMatiereId = $produit->transformation?->matierePremiere?->producteur_id;

        return $producteurMatiereId !== null && (int) $producteurMatiereId === (int) $user->id;
    }
}
