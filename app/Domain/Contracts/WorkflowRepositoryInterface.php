<?php

namespace App\Domain\Contracts;

use App\Domain\DTOs\ConfigureWorkflowDTO;
use App\Models\Form;
use App\Models\Workflow;

interface WorkflowRepositoryInterface
{
    public function findForForm(Form $form): ?Workflow;

    public function configure(Form $form, ConfigureWorkflowDTO $dto): Workflow;

    public function delete(Workflow $workflow): void;
}
