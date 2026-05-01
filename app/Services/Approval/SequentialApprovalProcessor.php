<?php

namespace App\Services\Approval;

use App\Domain\Contracts\ApprovalProcessorInterface;
use App\Domain\DTOs\ApprovalActionDTO;
use App\Domain\Enums\ApprovalAction;
use App\Domain\Enums\RequestStatus;
use App\Models\ApprovalAction as ApprovalActionModel;
use App\Models\ApprovalRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SequentialApprovalProcessor implements ApprovalProcessorInterface
{
    public function process(ApprovalRequest $request, User $approver, ApprovalActionDTO $action): ApprovalRequest
    {
        return DB::transaction(function () use ($request, $approver, $action) {
            $request->refresh();
            $request->loadMissing('workflow.steps');

            $stepOrder = $request->current_step_order;

            ApprovalActionModel::create([
                'approval_request_id' => $request->id,
                'approver_id' => $approver->id,
                'action' => $action->action,
                'comment' => $action->comment,
                'step_order' => $stepOrder,
                'acted_at' => now(),
            ]);

            if ($action->action === ApprovalAction::Reject) {
                $request->update([
                    'status' => RequestStatus::Rejected,
                    'current_step_order' => null,
                ]);

                return $request->fresh(['actions.approver']);
            }

            $maxStep = $request->workflow->steps->max('step_order');

            if ($stepOrder >= $maxStep) {
                $request->update([
                    'status' => RequestStatus::Approved,
                    'current_step_order' => null,
                ]);
            } else {
                $request->update(['current_step_order' => $stepOrder + 1]);
            }

            return $request->fresh(['actions.approver']);
        });
    }

    public function canApproverAct(ApprovalRequest $request, User $approver): bool
    {
        if ($request->status !== RequestStatus::Pending) {
            return false;
        }

        $request->loadMissing('workflow.steps');
        $currentStep = $request->workflow->steps->firstWhere('step_order', $request->current_step_order);

        return $currentStep?->approver_id === $approver->id;
    }
}
