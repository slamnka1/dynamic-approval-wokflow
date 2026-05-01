<?php

namespace App\Repositories;

use App\Domain\Contracts\WorkflowRepositoryInterface;
use App\Domain\DTOs\ConfigureWorkflowDTO;
use App\Domain\Enums\WorkflowType;
use App\Models\Form;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use Illuminate\Support\Facades\DB;

class EloquentWorkflowRepository implements WorkflowRepositoryInterface
{
    public function findForForm(Form $form): ?Workflow
    {
        return $form->workflow()->with('steps.approver')->first();
    }

    public function configure(Form $form, ConfigureWorkflowDTO $dto): Workflow
    {
        return DB::transaction(function () use ($form, $dto) {
            $required = $dto->type === WorkflowType::Threshold
                ? max(1, min($dto->requiredApprovals, count($dto->steps)))
                : count($dto->steps);

            $workflow = Workflow::updateOrCreate(
                ['form_id' => $form->id],
                [
                    'name' => $dto->name,
                    'type' => $dto->type,
                    'required_approvals' => $required,
                ],
            );

            $workflow->steps()->delete();

            foreach ($dto->steps as $i => $step) {
                WorkflowStep::create([
                    'workflow_id' => $workflow->id,
                    'step_order' => $i + 1,
                    'approver_id' => $step['approver_id'],
                ]);
            }

            return $workflow->load('steps.approver');
        });
    }

    public function delete(Workflow $workflow): void
    {
        $workflow->delete();
    }
}
