<?php

namespace App\Services;

use App\Domain\Contracts\ApprovalRequestRepositoryInterface;
use App\Domain\DTOs\ApprovalActionDTO;
use App\Models\ApprovalRequest;
use App\Models\User;
use App\Services\Approval\ApprovalProcessorFactory;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;

class ApprovalService
{
    public function __construct(
        private readonly ApprovalRequestRepositoryInterface $requests,
        private readonly ApprovalProcessorFactory $factory,
    ) {}

    public function pending(User $approver): Collection
    {
        return $this->requests->listPendingForApprover($approver);
    }

    public function past(User $approver): Collection
    {
        return $this->requests->listPastForApprover($approver);
    }

    public function act(ApprovalRequest $request, User $approver, ApprovalActionDTO $action): ApprovalRequest
    {
        $processor = $this->factory->for($request);

        if (! $processor->canApproverAct($request, $approver)) {
            throw new AuthorizationException('You are not authorised to act on this request right now.');
        }

        return $processor->process($request, $approver, $action);
    }
}
