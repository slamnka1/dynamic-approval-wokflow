<?php

namespace App\Domain\Contracts;

use App\Domain\DTOs\ApprovalActionDTO;
use App\Models\ApprovalRequest;
use App\Models\User;

interface ApprovalProcessorInterface
{
    /**
     * Apply an approve/reject decision and return the updated request
     * with status / current_step_order properly advanced.
     */
    public function process(ApprovalRequest $request, User $approver, ApprovalActionDTO $action): ApprovalRequest;

    /**
     * Whether the given approver may currently act on the request.
     */
    public function canApproverAct(ApprovalRequest $request, User $approver): bool;
}
