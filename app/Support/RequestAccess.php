<?php

namespace App\Support;

use App\Domain\Enums\UserRole;
use App\Models\ApprovalRequest;
use App\Models\User;

class RequestAccess
{
    /**
     * A request is visible to: admins, the original requester, any approver
     * assigned to the workflow, or any approver who has acted on it.
     * Everyone else gets a 403.
     */
    public static function canView(User $user, ApprovalRequest $request): bool
    {
        if ($user->role === UserRole::Admin) {
            return true;
        }

        if ($request->requester_id === $user->id) {
            return true;
        }

        $request->loadMissing(['workflow.steps', 'actions']);

        if ($request->workflow?->steps->contains('approver_id', $user->id)) {
            return true;
        }

        if ($request->actions->contains('approver_id', $user->id)) {
            return true;
        }

        return false;
    }
}
