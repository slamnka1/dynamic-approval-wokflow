<?php

namespace Database\Seeders;

use App\Domain\DTOs\ConfigureWorkflowDTO;
use App\Domain\DTOs\CreateFormDTO;
use App\Domain\Enums\UserRole;
use App\Models\User;
use App\Services\FormBuilderService;
use App\Services\WorkflowConfigurationService;
use Illuminate\Database\Seeder;

class DemoFormsSeeder extends Seeder
{
    public function __construct(
        private readonly FormBuilderService $forms,
        private readonly WorkflowConfigurationService $workflows,
    ) {}

    public function run(): void
    {
        $admin = User::where('role', UserRole::Admin)->firstOrFail();
        $approvers = User::where('role', UserRole::Approver)->orderBy('id')->get();

        if ($approvers->count() < 3) {
            return;
        }

        $expense = $this->forms->create(CreateFormDTO::fromArray([
            'name' => 'Expense Reimbursement',
            'description' => 'Submit a business expense for manager approval.',
            'is_active' => true,
            'fields' => [
                ['key' => 'title', 'label' => 'Title', 'type' => 'text', 'is_required' => true, 'placeholder' => 'e.g. Conference travel'],
                ['key' => 'amount', 'label' => 'Amount (USD)', 'type' => 'number', 'is_required' => true, 'min_value' => 1, 'max_value' => 10000],
                ['key' => 'category', 'label' => 'Category', 'type' => 'select', 'is_required' => true, 'options' => [
                    ['value' => 'travel', 'label' => 'Travel'],
                    ['value' => 'meals', 'label' => 'Meals'],
                    ['value' => 'software', 'label' => 'Software'],
                    ['value' => 'other', 'label' => 'Other'],
                ]],
                ['key' => 'incurred_on', 'label' => 'Date incurred', 'type' => 'date', 'is_required' => true],
                ['key' => 'reimbursable', 'label' => 'Already paid out of pocket?', 'type' => 'checkbox', 'is_required' => false],
                ['key' => 'notes', 'label' => 'Notes', 'type' => 'textarea', 'is_required' => false],
            ],
        ]), $admin->id);

        $this->workflows->configure($expense, ConfigureWorkflowDTO::fromArray([
            'name' => 'Two-step manager approval',
            'type' => 'sequential',
            'steps' => [
                ['approver_id' => $approvers[0]->id],
                ['approver_id' => $approvers[1]->id],
            ],
        ]));

        $pto = $this->forms->create(CreateFormDTO::fromArray([
            'name' => 'PTO Request',
            'description' => 'Request paid time off — needs sign-off from any 2 of 3 leads.',
            'is_active' => true,
            'fields' => [
                ['key' => 'start_date', 'label' => 'Start date', 'type' => 'date', 'is_required' => true],
                ['key' => 'days', 'label' => 'Number of days', 'type' => 'number', 'is_required' => true, 'min_value' => 1, 'max_value' => 30],
                ['key' => 'reason', 'label' => 'Reason', 'type' => 'textarea', 'is_required' => false, 'placeholder' => 'Optional context'],
            ],
        ]), $admin->id);

        $this->workflows->configure($pto, ConfigureWorkflowDTO::fromArray([
            'name' => 'Two-of-three leads',
            'type' => 'threshold',
            'required_approvals' => 2,
            'steps' => [
                ['approver_id' => $approvers[0]->id],
                ['approver_id' => $approvers[1]->id],
                ['approver_id' => $approvers[2]->id],
            ],
        ]));
    }
}
