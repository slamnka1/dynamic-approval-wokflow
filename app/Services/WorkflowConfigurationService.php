<?php

namespace App\Services;

use App\Domain\Contracts\WorkflowRepositoryInterface;
use App\Domain\DTOs\ConfigureWorkflowDTO;
use App\Domain\Enums\UserRole;
use App\Models\Form;
use App\Models\User;
use App\Models\Workflow;
use Illuminate\Validation\ValidationException;

class WorkflowConfigurationService
{
    public function __construct(
        private readonly WorkflowRepositoryInterface $workflows,
    ) {}

    public function configure(Form $form, ConfigureWorkflowDTO $dto): Workflow
    {
        if (count($dto->steps) === 0) {
            throw ValidationException::withMessages([
                'steps' => ['At least one approver step is required.'],
            ]);
        }

        $approverIds = array_map(fn ($s) => (int) $s['approver_id'], $dto->steps);
        $validApproverCount = User::whereIn('id', $approverIds)
            ->where('role', UserRole::Approver)
            ->count();

        if ($validApproverCount !== count(array_unique($approverIds))) {
            throw ValidationException::withMessages([
                'steps' => ['One or more approver IDs are invalid or not approvers.'],
            ]);
        }

        return $this->workflows->configure($form, $dto);
    }
}
