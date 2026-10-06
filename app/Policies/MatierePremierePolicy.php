<?php

namespace App\Policies;

use App\Models\MatierePremiere;
use App\Models\User;

class MatierePremierePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, MatierePremiere $matierePremiere): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isProducteur();
    }

    public function update(User $user, MatierePremiere $matierePremiere): bool
    {
        return $user->isAdmin() || ($user->isProducteur() && $matierePremiere->producteur_id === $user->id);
    }

    public function delete(User $user, MatierePremiere $matierePremiere): bool
    {
        return $this->update($user, $matierePremiere);
    }
}