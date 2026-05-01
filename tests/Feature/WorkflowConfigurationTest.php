<?php

namespace Tests\Feature;

use App\Domain\Enums\UserRole;
use App\Models\Form;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowConfigurationTest extends TestCase
{
    use RefreshDatabase;

    private function createForm(): Form
    {
        $admin = $this->actingAsRole(UserRole::Admin);

        $form = Form::create(['name' => 'F', 'created_by' => $admin->id, 'is_active' => true]);

        return $form;
    }

    public function test_admin_can_configure_sequential_workflow(): void
    {
        $form = $this->createForm();
        $a1 = $this->makeUser(UserRole::Approver);
        $a2 = $this->makeUser(UserRole::Approver);

        $resp = $this->postJson("/api/admin/forms/{$form->id}/workflow", [
            'name' => 'Two-step',
            'type' => 'sequential',
            'steps' => [
                ['approver_id' => $a1->id],
                ['approver_id' => $a2->id],
            ],
        ]);

        $resp->assertCreated();
        $resp->assertJsonPath('data.type', 'sequential');
        $resp->assertJsonPath('data.required_approvals', 2);
        $resp->assertJsonCount(2, 'data.steps');
    }

    public function test_admin_can_configure_threshold_workflow(): void
    {
        $form = $this->createForm();
        $a1 = $this->makeUser(UserRole::Approver);
        $a2 = $this->makeUser(UserRole::Approver);
        $a3 = $this->makeUser(UserRole::Approver);

        $resp = $this->postJson("/api/admin/forms/{$form->id}/workflow", [
            'name' => '2-of-3',
            'type' => 'threshold',
            'required_approvals' => 2,
            'steps' => [
                ['approver_id' => $a1->id],
                ['approver_id' => $a2->id],
                ['approver_id' => $a3->id],
            ],
        ]);

        $resp->assertCreated();
        $resp->assertJsonPath('data.type', 'threshold');
        $resp->assertJsonPath('data.required_approvals', 2);
    }

    public function test_workflow_rejects_non_approver_users_in_steps(): void
    {
        $form = $this->createForm();
        $requester = $this->makeUser(UserRole::Requester);

        $this->postJson("/api/admin/forms/{$form->id}/workflow", [
            'name' => 'Bad',
            'type' => 'sequential',
            'steps' => [['approver_id' => $requester->id]],
        ])->assertUnprocessable();
    }

    public function test_workflow_requires_at_least_one_step(): void
    {
        $form = $this->createForm();

        $this->postJson("/api/admin/forms/{$form->id}/workflow", [
            'name' => 'Empty',
            'type' => 'sequential',
            'steps' => [],
        ])->assertUnprocessable();
    }

    public function test_configure_replaces_existing_workflow(): void
    {
        $form = $this->createForm();
        $a1 = $this->makeUser(UserRole::Approver);
        $a2 = $this->makeUser(UserRole::Approver);

        $this->postJson("/api/admin/forms/{$form->id}/workflow", [
            'name' => 'V1', 'type' => 'sequential', 'steps' => [['approver_id' => $a1->id]],
        ])->assertCreated();

        $resp = $this->postJson("/api/admin/forms/{$form->id}/workflow", [
            'name' => 'V2', 'type' => 'sequential', 'steps' => [['approver_id' => $a2->id]],
        ]);

        $resp->assertCreated();
        $resp->assertJsonPath('data.name', 'V2');
        $this->assertSame(1, $form->workflow()->count(), 'Only one workflow per form.');
    }

    public function test_threshold_required_approvals_clamped_to_step_count(): void
    {
        $form = $this->createForm();
        $a1 = $this->makeUser(UserRole::Approver);
        $a2 = $this->makeUser(UserRole::Approver);

        $resp = $this->postJson("/api/admin/forms/{$form->id}/workflow", [
            'name' => 'Over',
            'type' => 'threshold',
            'required_approvals' => 99,
            'steps' => [['approver_id' => $a1->id], ['approver_id' => $a2->id]],
        ]);

        $resp->assertCreated();
        $resp->assertJsonPath('data.required_approvals', 2);
    }
}
