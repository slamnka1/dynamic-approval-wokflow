<?php

namespace Tests\Feature;

use App\Domain\Enums\UserRole;
use App\Models\Form;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminFormTest extends TestCase
{
    use RefreshDatabase;

    private function validFormPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Expense',
            'description' => 'Submit expenses.',
            'is_active' => true,
            'fields' => [
                ['key' => 'title', 'label' => 'Title', 'type' => 'text', 'is_required' => true],
                ['key' => 'amount', 'label' => 'Amount', 'type' => 'number', 'is_required' => true, 'min_value' => 1, 'max_value' => 1000],
                ['key' => 'category', 'label' => 'Category', 'type' => 'select', 'is_required' => true, 'options' => [
                    ['value' => 'travel', 'label' => 'Travel'],
                    ['value' => 'meals', 'label' => 'Meals'],
                ]],
            ],
        ], $overrides);
    }

    public function test_admin_can_create_form_with_normalised_fields(): void
    {
        $this->actingAsRole(UserRole::Admin);

        $resp = $this->postJson('/api/admin/forms', $this->validFormPayload());

        $resp->assertCreated();
        $resp->assertJsonPath('data.fields.0.key', 'title');
        $resp->assertJsonPath('data.fields.2.type', 'select');
        $resp->assertJsonCount(2, 'data.fields.2.options');
    }

    public function test_field_keys_must_be_unique(): void
    {
        $this->actingAsRole(UserRole::Admin);

        $this->postJson('/api/admin/forms', $this->validFormPayload([
            'fields' => [
                ['key' => 'dup', 'label' => 'A', 'type' => 'text'],
                ['key' => 'dup', 'label' => 'B', 'type' => 'text'],
            ],
        ]))->assertUnprocessable();
    }

    public function test_select_field_requires_at_least_one_option(): void
    {
        $this->actingAsRole(UserRole::Admin);

        $this->postJson('/api/admin/forms', $this->validFormPayload([
            'fields' => [['key' => 'cat', 'label' => 'C', 'type' => 'select']],
        ]))->assertUnprocessable();
    }

    public function test_field_key_must_be_slug(): void
    {
        $this->actingAsRole(UserRole::Admin);

        $this->postJson('/api/admin/forms', $this->validFormPayload([
            'fields' => [['key' => 'Bad-Key!', 'label' => 'B', 'type' => 'text']],
        ]))->assertUnprocessable();
    }

    public function test_admin_can_list_all_forms(): void
    {
        $this->actingAsRole(UserRole::Admin);
        $this->postJson('/api/admin/forms', $this->validFormPayload(['name' => 'A']));
        $this->postJson('/api/admin/forms', $this->validFormPayload(['name' => 'B', 'is_active' => false]));

        $resp = $this->getJson('/api/admin/forms');
        $resp->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_admin_can_update_form(): void
    {
        $admin = $this->actingAsRole(UserRole::Admin);
        $created = $this->postJson('/api/admin/forms', $this->validFormPayload())->json('data');

        $resp = $this->putJson("/api/admin/forms/{$created['id']}", $this->validFormPayload([
            'name' => 'Renamed',
            'fields' => [['key' => 'only', 'label' => 'Only', 'type' => 'text']],
        ]));

        $resp->assertOk();
        $resp->assertJsonPath('data.name', 'Renamed');
        $resp->assertJsonCount(1, 'data.fields');
    }

    public function test_admin_can_delete_form(): void
    {
        $this->actingAsRole(UserRole::Admin);
        $created = $this->postJson('/api/admin/forms', $this->validFormPayload())->json('data');

        $this->deleteJson("/api/admin/forms/{$created['id']}")->assertOk();
        $this->assertDatabaseMissing('forms', ['id' => $created['id']]);
    }

    public function test_public_forms_list_is_filtered_to_active(): void
    {
        $admin = $this->actingAsRole(UserRole::Admin);
        $active = $this->postJson('/api/admin/forms', $this->validFormPayload(['name' => 'Active']))->json('data');
        $inactive = $this->postJson('/api/admin/forms', $this->validFormPayload(['name' => 'Hidden', 'is_active' => false]))->json('data');

        $this->actingAsRole(UserRole::Requester);
        $resp = $this->getJson('/api/forms');

        $resp->assertOk();
        $ids = collect($resp->json('data'))->pluck('id')->all();
        $this->assertContains($active['id'], $ids);
        $this->assertNotContains($inactive['id'], $ids);
    }

    public function test_admin_can_list_users_by_role(): void
    {
        $this->actingAsRole(UserRole::Admin);
        $this->makeUser(UserRole::Approver);
        $this->makeUser(UserRole::Approver);
        $this->makeUser(UserRole::Requester);

        $resp = $this->getJson('/api/admin/users?role=approver');
        $resp->assertOk();
        $emails = collect($resp->json('data'))->pluck('role')->unique()->values()->all();
        $this->assertSame(['approver'], $emails);
    }
}
