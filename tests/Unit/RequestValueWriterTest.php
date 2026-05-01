<?php

namespace Tests\Unit;

use App\Domain\Enums\UserRole;
use App\Domain\Enums\WorkflowType;
use App\Models\ApprovalRequest;
use App\Models\Form;
use App\Models\FormField;
use App\Models\Workflow;
use App\Persistence\RequestValueWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestValueWriterTest extends TestCase
{
    use RefreshDatabase;

    private function ctx(): array
    {
        $admin = $this->makeUser(UserRole::Admin);
        $req = $this->makeUser(UserRole::Requester);
        $form = Form::create(['name' => 'F', 'created_by' => $admin->id, 'is_active' => true]);
        $workflow = Workflow::create([
            'form_id' => $form->id,
            'name' => 'W',
            'type' => WorkflowType::Sequential,
            'required_approvals' => 1,
        ]);
        $approvalReq = ApprovalRequest::create([
            'form_id' => $form->id,
            'workflow_id' => $workflow->id,
            'requester_id' => $req->id,
            'status' => \App\Domain\Enums\RequestStatus::Pending,
        ]);

        return [$form, $approvalReq];
    }

    private function field(Form $form, string $type): FormField
    {
        return FormField::create([
            'form_id' => $form->id,
            'key' => 'k_'.$type,
            'label' => 'L',
            'type' => $type,
            'is_required' => false,
            'sort_order' => 0,
        ]);
    }

    public function test_text_routes_to_value_string(): void
    {
        [$form, $req] = $this->ctx();
        $f = $this->field($form, 'text');
        $row = app(RequestValueWriter::class)->write($req, $f, 'hello');

        $this->assertSame('hello', $row->value_string);
        $this->assertNull($row->value_number);
    }

    public function test_number_routes_to_value_number(): void
    {
        [$form, $req] = $this->ctx();
        $f = $this->field($form, 'number');
        $row = app(RequestValueWriter::class)->write($req, $f, '42.5');

        $this->assertEqualsWithDelta(42.5, (float) $row->value_number, 0.001);
        $this->assertNull($row->value_string);
    }

    public function test_date_routes_to_value_date(): void
    {
        [$form, $req] = $this->ctx();
        $f = $this->field($form, 'date');
        $row = app(RequestValueWriter::class)->write($req, $f, '2026-04-12');

        $this->assertNotNull($row->value_date);
        $this->assertSame('2026-04-12', $row->value_date->toDateString());
    }

    public function test_checkbox_routes_to_value_boolean(): void
    {
        [$form, $req] = $this->ctx();
        $f = $this->field($form, 'checkbox');
        $row = app(RequestValueWriter::class)->write($req, $f, true);

        $this->assertTrue($row->value_boolean);
    }

    public function test_select_routes_to_value_string(): void
    {
        [$form, $req] = $this->ctx();
        $f = $this->field($form, 'select');
        $row = app(RequestValueWriter::class)->write($req, $f, 'travel');

        $this->assertSame('travel', $row->value_string);
    }

    public function test_textarea_routes_to_value_string(): void
    {
        [$form, $req] = $this->ctx();
        $f = $this->field($form, 'textarea');
        $row = app(RequestValueWriter::class)->write($req, $f, 'long text');

        $this->assertSame('long text', $row->value_string);
    }
}
