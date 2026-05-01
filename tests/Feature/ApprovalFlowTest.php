<?php

namespace Tests\Feature;

use App\Domain\Enums\UserRole;
use App\Models\Form;
use App\Models\FormField;
use App\Models\FormFieldOption;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApprovalFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $requester;

    /** @var array<int, User> */
    private array $approvers = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->makeUser(UserRole::Admin);
        $this->requester = $this->makeUser(UserRole::Requester);
        $this->approvers = [
            $this->makeUser(UserRole::Approver),
            $this->makeUser(UserRole::Approver),
            $this->makeUser(UserRole::Approver),
        ];
    }

    private function buildForm(): Form
    {
        $form = Form::create(['name' => 'Expense', 'created_by' => $this->admin->id, 'is_active' => true]);

        FormField::create(['form_id' => $form->id, 'key' => 'title', 'label' => 'T', 'type' => 'text', 'is_required' => true, 'sort_order' => 0]);
        FormField::create(['form_id' => $form->id, 'key' => 'amount', 'label' => 'A', 'type' => 'number', 'is_required' => true, 'min_value' => 1, 'max_value' => 1000, 'sort_order' => 1]);

        $cat = FormField::create(['form_id' => $form->id, 'key' => 'category', 'label' => 'C', 'type' => 'select', 'is_required' => true, 'sort_order' => 2]);
        FormFieldOption::create(['form_field_id' => $cat->id, 'value' => 'travel', 'label' => 'Travel', 'sort_order' => 0]);
        FormFieldOption::create(['form_field_id' => $cat->id, 'value' => 'meals', 'label' => 'Meals', 'sort_order' => 1]);

        return $form;
    }

    private function buildSequentialWorkflow(Form $form): Workflow
    {
        $wf = Workflow::create([
            'form_id' => $form->id,
            'name' => 'Seq',
            'type' => 'sequential',
            'required_approvals' => 2,
        ]);
        WorkflowStep::create(['workflow_id' => $wf->id, 'step_order' => 1, 'approver_id' => $this->approvers[0]->id]);
        WorkflowStep::create(['workflow_id' => $wf->id, 'step_order' => 2, 'approver_id' => $this->approvers[1]->id]);

        return $wf;
    }

    private function buildThresholdWorkflow(Form $form, int $required = 2): Workflow
    {
        $wf = Workflow::create([
            'form_id' => $form->id,
            'name' => 'Thr',
            'type' => 'threshold',
            'required_approvals' => $required,
        ]);
        foreach ($this->approvers as $i => $u) {
            WorkflowStep::create(['workflow_id' => $wf->id, 'step_order' => $i + 1, 'approver_id' => $u->id]);
        }

        return $wf;
    }

    private function submitFor(Form $form, User $as, array $values): int
    {
        Sanctum::actingAs($as, ['*']);

        return $this->postJson("/api/forms/{$form->id}/requests", ['values' => $values])
            ->assertCreated()
            ->json('data.id');
    }

    public function test_requester_can_submit_a_filled_form(): void
    {
        $form = $this->buildForm();
        $this->buildSequentialWorkflow($form);

        $id = $this->submitFor($form, $this->requester, [
            'title' => 'NYC', 'amount' => 200, 'category' => 'travel',
        ]);

        $this->assertDatabaseHas('approval_requests', ['id' => $id, 'status' => 'pending', 'current_step_order' => 1]);
        $this->assertDatabaseCount('approval_request_values', 3);
    }

    public function test_submission_fails_validation_for_out_of_range_number(): void
    {
        $form = $this->buildForm();
        $this->buildSequentialWorkflow($form);

        Sanctum::actingAs($this->requester, ['*']);
        $this->postJson("/api/forms/{$form->id}/requests", [
            'values' => ['title' => 'X', 'amount' => 99999, 'category' => 'travel'],
        ])->assertUnprocessable();
    }

    public function test_submission_fails_for_invalid_select_option(): void
    {
        $form = $this->buildForm();
        $this->buildSequentialWorkflow($form);

        Sanctum::actingAs($this->requester, ['*']);
        $this->postJson("/api/forms/{$form->id}/requests", [
            'values' => ['title' => 'X', 'amount' => 50, 'category' => 'invalid'],
        ])->assertUnprocessable();
    }

    public function test_submission_fails_for_missing_required(): void
    {
        $form = $this->buildForm();
        $this->buildSequentialWorkflow($form);

        Sanctum::actingAs($this->requester, ['*']);
        $this->postJson("/api/forms/{$form->id}/requests", ['values' => ['amount' => 50]])
            ->assertUnprocessable();
    }

    public function test_inactive_form_rejects_submissions(): void
    {
        $form = $this->buildForm();
        $this->buildSequentialWorkflow($form);
        $form->update(['is_active' => false]);

        Sanctum::actingAs($this->requester, ['*']);
        $this->postJson("/api/forms/{$form->id}/requests", [
            'values' => ['title' => 'X', 'amount' => 1, 'category' => 'travel'],
        ])->assertUnprocessable();
    }

    public function test_form_without_workflow_rejects_submissions(): void
    {
        $form = $this->buildForm();

        Sanctum::actingAs($this->requester, ['*']);
        $this->postJson("/api/forms/{$form->id}/requests", [
            'values' => ['title' => 'X', 'amount' => 1, 'category' => 'travel'],
        ])->assertUnprocessable();
    }

    public function test_sequential_full_approval_path(): void
    {
        $form = $this->buildForm();
        $this->buildSequentialWorkflow($form);
        $id = $this->submitFor($form, $this->requester, ['title' => 'X', 'amount' => 50, 'category' => 'travel']);

        // approver 1 sees pending, approver 2 does not
        Sanctum::actingAs($this->approvers[0], ['*']);
        $this->getJson('/api/approvals/pending')->assertOk()->assertJsonCount(1, 'data');

        Sanctum::actingAs($this->approvers[1], ['*']);
        $this->getJson('/api/approvals/pending')->assertOk()->assertJsonCount(0, 'data');

        // out-of-order attempt fails
        $this->postJson("/api/approvals/{$id}/approve", [])->assertForbidden();

        // approver 1 approves → still pending, current step 2
        Sanctum::actingAs($this->approvers[0], ['*']);
        $this->postJson("/api/approvals/{$id}/approve", ['comment' => 'ok'])
            ->assertOk()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.current_step_order', 2);

        // approver 2 sees pending now, then approves → approved
        Sanctum::actingAs($this->approvers[1], ['*']);
        $this->getJson('/api/approvals/pending')->assertJsonCount(1, 'data');

        $this->postJson("/api/approvals/{$id}/approve", [])
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');
    }

    public function test_sequential_reject_at_first_step_terminates(): void
    {
        $form = $this->buildForm();
        $this->buildSequentialWorkflow($form);
        $id = $this->submitFor($form, $this->requester, ['title' => 'X', 'amount' => 50, 'category' => 'travel']);

        Sanctum::actingAs($this->approvers[0], ['*']);
        $this->postJson("/api/approvals/{$id}/reject", ['comment' => 'no'])
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected');

        // approver 2 cannot act on rejected request
        Sanctum::actingAs($this->approvers[1], ['*']);
        $this->postJson("/api/approvals/{$id}/approve", [])->assertForbidden();
    }

    public function test_threshold_two_of_three(): void
    {
        $form = $this->buildForm();
        $this->buildThresholdWorkflow($form, required: 2);
        $id = $this->submitFor($form, $this->requester, ['title' => 'X', 'amount' => 5, 'category' => 'travel']);

        // all 3 approvers see pending
        foreach ($this->approvers as $u) {
            Sanctum::actingAs($u, ['*']);
            $this->getJson('/api/approvals/pending')->assertJsonCount(1, 'data');
        }

        // first approver acts
        Sanctum::actingAs($this->approvers[0], ['*']);
        $this->postJson("/api/approvals/{$id}/approve", [])->assertJsonPath('data.status', 'pending');

        // first approver cannot re-approve
        $this->postJson("/api/approvals/{$id}/approve", [])->assertForbidden();
        $this->getJson('/api/approvals/pending')->assertJsonCount(0, 'data');

        // second approver hits threshold
        Sanctum::actingAs($this->approvers[1], ['*']);
        $this->postJson("/api/approvals/{$id}/approve", [])->assertJsonPath('data.status', 'approved');

        // third approver now blocked (request completed)
        Sanctum::actingAs($this->approvers[2], ['*']);
        $this->postJson("/api/approvals/{$id}/approve", [])->assertForbidden();
    }

    public function test_threshold_single_reject_terminates(): void
    {
        $form = $this->buildForm();
        $this->buildThresholdWorkflow($form, required: 2);
        $id = $this->submitFor($form, $this->requester, ['title' => 'X', 'amount' => 5, 'category' => 'travel']);

        Sanctum::actingAs($this->approvers[1], ['*']);
        $this->postJson("/api/approvals/{$id}/reject", ['comment' => 'no'])
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected');
    }

    public function test_requester_lists_only_their_own_requests(): void
    {
        $form = $this->buildForm();
        $this->buildSequentialWorkflow($form);

        $other = $this->makeUser(UserRole::Requester);
        $this->submitFor($form, $this->requester, ['title' => 'mine', 'amount' => 1, 'category' => 'travel']);
        $this->submitFor($form, $other, ['title' => 'theirs', 'amount' => 1, 'category' => 'meals']);

        Sanctum::actingAs($this->requester, ['*']);
        $resp = $this->getJson('/api/my/requests');
        $resp->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_requester_cannot_view_someone_elses_request_detail(): void
    {
        $form = $this->buildForm();
        $this->buildSequentialWorkflow($form);
        $other = $this->makeUser(UserRole::Requester);
        $id = $this->submitFor($form, $other, ['title' => 'X', 'amount' => 1, 'category' => 'travel']);

        Sanctum::actingAs($this->requester, ['*']);
        $this->getJson("/api/my/requests/{$id}")->assertForbidden();
    }

    public function test_approver_can_view_request_they_have_acted_on(): void
    {
        $form = $this->buildForm();
        $this->buildSequentialWorkflow($form);
        $id = $this->submitFor($form, $this->requester, ['title' => 'X', 'amount' => 1, 'category' => 'travel']);

        Sanctum::actingAs($this->approvers[0], ['*']);
        $this->postJson("/api/approvals/{$id}/approve", [])->assertOk();

        $this->getJson("/api/approvals/{$id}")->assertOk()->assertJsonPath('data.id', $id);
    }

    public function test_unrelated_approver_cannot_view_request(): void
    {
        $form = $this->buildForm();
        $this->buildSequentialWorkflow($form); // only approvers[0] and approvers[1] in workflow
        $id = $this->submitFor($form, $this->requester, ['title' => 'X', 'amount' => 1, 'category' => 'travel']);

        // approvers[2] is approver-role but not in this workflow
        Sanctum::actingAs($this->approvers[2], ['*']);
        $this->getJson("/api/approvals/{$id}")->assertForbidden();
    }

    public function test_past_approvals_lists_acted_on_requests(): void
    {
        $form = $this->buildForm();
        $this->buildSequentialWorkflow($form);
        $id = $this->submitFor($form, $this->requester, ['title' => 'X', 'amount' => 1, 'category' => 'travel']);

        Sanctum::actingAs($this->approvers[0], ['*']);
        $this->postJson("/api/approvals/{$id}/reject", [])->assertOk();

        $resp = $this->getJson('/api/approvals/past');
        $resp->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_typed_value_columns_routed_correctly(): void
    {
        $form = Form::create(['name' => 'Mixed', 'created_by' => $this->admin->id, 'is_active' => true]);
        FormField::create(['form_id' => $form->id, 'key' => 'name', 'label' => 'N', 'type' => 'text', 'is_required' => true, 'sort_order' => 0]);
        FormField::create(['form_id' => $form->id, 'key' => 'qty', 'label' => 'Q', 'type' => 'number', 'is_required' => true, 'sort_order' => 1]);
        FormField::create(['form_id' => $form->id, 'key' => 'when', 'label' => 'W', 'type' => 'date', 'is_required' => true, 'sort_order' => 2]);
        FormField::create(['form_id' => $form->id, 'key' => 'agree', 'label' => 'A', 'type' => 'checkbox', 'is_required' => false, 'sort_order' => 3]);
        $this->buildSequentialWorkflow($form);

        $id = $this->submitFor($form, $this->requester, [
            'name' => 'foo', 'qty' => 7, 'when' => '2026-04-12', 'agree' => true,
        ]);

        $this->assertDatabaseHas('approval_request_values', ['approval_request_id' => $id, 'value_string' => 'foo']);
        $this->assertDatabaseHas('approval_request_values', ['approval_request_id' => $id, 'value_number' => 7]);
        $this->assertDatabaseHas('approval_request_values', ['approval_request_id' => $id, 'value_boolean' => 1]);

        $dateRow = \Illuminate\Support\Facades\DB::table('approval_request_values')
            ->where('approval_request_id', $id)
            ->whereNotNull('value_date')
            ->first();
        $this->assertNotNull($dateRow);
        $this->assertStringStartsWith('2026-04-12', (string) $dateRow->value_date);
    }
}
