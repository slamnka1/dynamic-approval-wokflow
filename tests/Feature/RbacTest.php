<?php

namespace Tests\Feature;

use App\Domain\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_api_request_returns_401_json(): void
    {
        $this->getJson('/api/forms')->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_unauthenticated_api_request_without_accept_header_returns_401(): void
    {
        // hits the api group which forces JSON via ForceJsonResponse middleware
        $resp = $this->get('/api/forms');
        $resp->assertUnauthorized();
    }

    public function test_requester_cannot_access_admin_endpoints(): void
    {
        $this->actingAsRole(UserRole::Requester);

        $this->getJson('/api/admin/forms')->assertForbidden();
        $this->getJson('/api/admin/users')->assertForbidden();
    }

    public function test_approver_cannot_access_admin_endpoints(): void
    {
        $this->actingAsRole(UserRole::Approver);

        $this->getJson('/api/admin/forms')->assertForbidden();
    }

    public function test_requester_cannot_access_approver_endpoints(): void
    {
        $this->actingAsRole(UserRole::Requester);

        $this->getJson('/api/approvals/pending')->assertForbidden();
        $this->getJson('/api/approvals/past')->assertForbidden();
    }

    public function test_admin_cannot_access_approver_endpoints(): void
    {
        $this->actingAsRole(UserRole::Admin);

        $this->getJson('/api/approvals/pending')->assertForbidden();
    }

    public function test_admin_can_access_admin_endpoints(): void
    {
        $this->actingAsRole(UserRole::Admin);

        $this->getJson('/api/admin/forms')->assertOk();
        $this->getJson('/api/admin/users')->assertOk();
    }

    public function test_approver_can_access_approver_endpoints(): void
    {
        $this->actingAsRole(UserRole::Approver);

        $this->getJson('/api/approvals/pending')->assertOk();
        $this->getJson('/api/approvals/past')->assertOk();
    }
}
