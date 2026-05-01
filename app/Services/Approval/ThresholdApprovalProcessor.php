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

class ThresholdApprovalProcessor implements ApprovalProcessorInterface
{
    public function process(ApprovalRequest $request, User $approver, ApprovalActionDTO $action): ApprovalRequest
    {
        return DB::transaction(function () use ($request, $approver, $action) {
            $request->refresh();
            $request->loadMissing(['workflow', 'actions']);

            ApprovalActionModel::create([
                'approval_request_id' => $request->id,
                'approver_id' => $approver->id,
                'action' => $action->action,
                'comment' => $action->comment,
                'step_order' => null,
                'acted_at' => now(),
            ]);

            if ($action->action === ApprovalAction::Reject) {
                $request->update(['status' => RequestStatus::Rejected]);

                return $request->fresh(['actions.approver']);
            }

            $approveCount = $request->actions()
                ->where('action', ApprovalAction::Approve)
                ->count();

            if ($approveCount >= $request->workflow->required_approvals) {
                $request->update(['status' => RequestStatus::Approved]);
            }

            return $request->fresh(['actions.approver']);
        });
    }

    public function canApproverAct(ApprovalRequest $request, User $approver): bool
    {
        if ($request->status !== RequestStatus::Pending) {
            return false;
        }

        $request->loadMissing(['workflow.steps', 'actions']);

        $inPool = $request->workflow->steps->contains('approver_id', $approver->id);
        $alreadyActed = $request->actions->contains('approver_id', $approver->id);

        return $inPool && ! $alreadyActed;
    }
}
