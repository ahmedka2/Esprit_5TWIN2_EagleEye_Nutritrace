<?php

namespace App\Policies;

use App\Models\Certification;
use App\Models\User;

class CertificationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Certification $certification): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Certification $certification): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Certification $certification): bool
    {
        return $user->isAdmin();
    }
}