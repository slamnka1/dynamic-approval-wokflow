<?php

namespace Tests\Unit;

use App\Domain\Enums\UserRole;
use App\Models\Form;
use App\Models\FormField;
use App\Models\FormFieldOption;
use App\Validation\DynamicFieldValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DynamicFieldValidatorTest extends TestCase
{
    use RefreshDatabase;

    private function form(array $fields): Form
    {
        $admin = $this->makeUser(UserRole::Admin);
        $form = Form::create(['name' => 'T', 'created_by' => $admin->id, 'is_active' => true]);
        foreach ($fields as $i => $f) {
            $field = FormField::create([
                'form_id' => $form->id,
                'key' => $f['key'],
                'label' => $f['label'] ?? $f['key'],
                'type' => $f['type'],
                'is_required' => $f['is_required'] ?? false,
                'sort_order' => $i,
                'min_value' => $f['min_value'] ?? null,
                'max_value' => $f['max_value'] ?? null,
            ]);
            foreach ($f['options'] ?? [] as $j => $opt) {
                FormFieldOption::create([
                    'form_field_id' => $field->id,
                    'value' => $opt['value'],
                    'label' => $opt['label'],
                    'sort_order' => $j,
                ]);
            }
        }

        return $form;
    }

    public function test_required_field_must_be_present(): void
    {
        $form = $this->form([['key' => 'title', 'type' => 'text', 'is_required' => true]]);

        $this->expectException(ValidationException::class);
        app(DynamicFieldValidator::class)->validate($form, []);
    }

    public function test_optional_field_can_be_missing(): void
    {
        $form = $this->form([['key' => 'notes', 'type' => 'textarea', 'is_required' => false]]);

        $result = app(DynamicFieldValidator::class)->validate($form, []);
        $this->assertArrayHasKey('notes', $result);
    }

    public function test_number_min_max_enforced(): void
    {
        $form = $this->form([['key' => 'amount', 'type' => 'number', 'is_required' => true, 'min_value' => 1, 'max_value' => 100]]);

        $this->expectException(ValidationException::class);
        app(DynamicFieldValidator::class)->validate($form, ['amount' => 999]);
    }

    public function test_number_within_range_passes(): void
    {
        $form = $this->form([['key' => 'amount', 'type' => 'number', 'is_required' => true, 'min_value' => 1, 'max_value' => 100]]);

        $result = app(DynamicFieldValidator::class)->validate($form, ['amount' => 42]);
        $this->assertEquals(42, $result['amount']);
    }

    public function test_select_must_be_one_of_options(): void
    {
        $form = $this->form([[
            'key' => 'category',
            'type' => 'select',
            'is_required' => true,
            'options' => [
                ['value' => 'travel', 'label' => 'Travel'],
                ['value' => 'meals', 'label' => 'Meals'],
            ],
        ]]);

        $ok = app(DynamicFieldValidator::class)->validate($form, ['category' => 'travel']);
        $this->assertSame('travel', $ok['category']);

        $this->expectException(ValidationException::class);
        app(DynamicFieldValidator::class)->validate($form, ['category' => 'invalid']);
    }

    public function test_date_field_validated(): void
    {
        $form = $this->form([['key' => 'when', 'type' => 'date', 'is_required' => true]]);

        $this->expectException(ValidationException::class);
        app(DynamicFieldValidator::class)->validate($form, ['when' => 'not-a-date']);
    }

    public function test_checkbox_accepts_boolean(): void
    {
        $form = $this->form([['key' => 'agree', 'type' => 'checkbox', 'is_required' => false]]);

        $result = app(DynamicFieldValidator::class)->validate($form, ['agree' => true]);
        $this->assertTrue((bool) $result['agree']);
    }
}
