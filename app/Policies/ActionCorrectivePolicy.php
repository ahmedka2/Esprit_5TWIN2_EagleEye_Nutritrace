<?php

namespace App\Policies;

use App\Models\ActionCorrective;
use App\Models\SignalementQualite;
use App\Models\User;

class ActionCorrectivePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    public function create(User $user, SignalementQualite $signalement): bool
    {
        return app(SignalementQualitePolicy::class)->createActionCorrective($user, $signalement);
    }

    public function update(User $user, ActionCorrective $action): bool
    {
        $signalement = $action->signalementQualite;
        if (! $signalement) {
            return false;
        }

        return app(SignalementQualitePolicy::class)->updateActionCorrective($user, $signalement, $action);
    }

    public function delete(User $user, ActionCorrective $action): bool
    {
        $signalement = $action->signalementQualite;
        if (! $signalement) {
            return false;
        }

        return app(SignalementQualitePolicy::class)->deleteActionCorrective($user, $signalement, $action);
    }
}
