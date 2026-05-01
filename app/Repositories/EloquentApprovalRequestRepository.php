<?php

namespace App\Repositories;

use App\Domain\Contracts\ApprovalRequestRepositoryInterface;
use App\Domain\DTOs\SubmitRequestDTO;
use App\Domain\Enums\RequestStatus;
use App\Domain\Enums\WorkflowType;
use App\Models\ApprovalRequest;
use App\Models\Form;
use App\Models\User;
use App\Persistence\RequestValueWriter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EloquentApprovalRequestRepository implements ApprovalRequestRepositoryInterface
{
    public function __construct(
        private readonly RequestValueWriter $valueWriter,
    ) {}

    public function find(int $id): ?ApprovalRequest
    {
        return ApprovalRequest::with([
            'form.fields.options',
            'workflow.steps.approver',
            'values.field',
            'actions.approver',
            'requester',
        ])->find($id);
    }

    public function listForRequester(User $user): Collection
    {
        return ApprovalRequest::with(['form:id,name', 'workflow:id,name,type', 'actions.approver'])
            ->where('requester_id', $user->id)
            ->orderByDesc('id')
            ->get();
    }

    public function listPendingForApprover(User $user): Collection
    {
        return ApprovalRequest::with(['form:id,name', 'workflow.steps', 'requester:id,name,email', 'actions'])
            ->where('status', RequestStatus::Pending)
            ->whereHas('workflow.steps', fn ($q) => $q->where('approver_id', $user->id))
            ->get()
            ->filter(function (ApprovalRequest $r) use ($user) {
                if ($r->workflow->type === WorkflowType::Sequential) {
                    return $r->workflow->steps
                        ->firstWhere('step_order', $r->current_step_order)
                        ?->approver_id === $user->id;
                }

                return ! $r->actions->contains('approver_id', $user->id);
            })
            ->values();
    }

    public function listPastForApprover(User $user): Collection
    {
        $ids = ApprovalRequest::whereHas(
            'actions',
            fn ($q) => $q->where('approver_id', $user->id),
        )->pluck('id');

        return ApprovalRequest::with(['form:id,name', 'workflow:id,name,type', 'actions.approver', 'requester:id,name,email'])
            ->whereIn('id', $ids)
            ->orderByDesc('id')
            ->get();
    }

    public function create(Form $form, User $requester, SubmitRequestDTO $dto): ApprovalRequest
    {
        $form->loadMissing(['fields', 'workflow.steps']);

        return DB::transaction(function () use ($form, $requester, $dto) {
            $workflow = $form->workflow;

            $request = ApprovalRequest::create([
                'form_id' => $form->id,
                'workflow_id' => $workflow?->id,
                'requester_id' => $requester->id,
                'status' => RequestStatus::Pending,
                'current_step_order' => $workflow?->type === WorkflowType::Sequential ? 1 : null,
            ]);

            foreach ($form->fields as $field) {
                $raw = $dto->values[$field->key] ?? null;
                $this->valueWriter->write($request, $field, $raw);
            }

            return $request->load(['form.fields.options', 'workflow.steps.approver', 'values.field']);
        });
    }
}
