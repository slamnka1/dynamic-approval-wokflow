<?php

namespace Tests\Unit;

use App\Domain\Contracts\FormRepositoryInterface;
use App\Domain\DTOs\CreateFormDTO;
use App\Domain\Enums\UserRole;
use App\Repositories\Decorators\CachedFormRepository;
use App\Repositories\EloquentFormRepository;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CachedFormRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private function repo(): CachedFormRepository
    {
        return new CachedFormRepository(
            new EloquentFormRepository,
            app(CacheRepository::class),
        );
    }

    public function test_list_active_caches_result(): void
    {
        $admin = $this->makeUser(UserRole::Admin);
        $repo = $this->repo();

        $repo->create(CreateFormDTO::fromArray([
            'name' => 'F1',
            'is_active' => true,
            'fields' => [['key' => 'k', 'label' => 'L', 'type' => 'text']],
        ]), $admin->id);

        $repo->listActive(); // populate cache

        $this->assertTrue(Cache::has('forms:active:list'));
    }

    public function test_create_busts_active_list_cache(): void
    {
        $admin = $this->makeUser(UserRole::Admin);
        $repo = $this->repo();
        Cache::put('forms:active:list', collect(['stale']), 600);

        $repo->create(CreateFormDTO::fromArray([
            'name' => 'F1',
            'is_active' => true,
            'fields' => [['key' => 'k', 'label' => 'L', 'type' => 'text']],
        ]), $admin->id);

        $this->assertFalse(Cache::has('forms:active:list'));
    }

    public function test_update_busts_caches_for_form(): void
    {
        $admin = $this->makeUser(UserRole::Admin);
        $repo = $this->repo();
        $form = $repo->create(CreateFormDTO::fromArray([
            'name' => 'F1',
            'is_active' => true,
            'fields' => [['key' => 'k', 'label' => 'L', 'type' => 'text']],
        ]), $admin->id);

        $repo->find($form->id);
        Cache::put('forms:active:list', collect(['stale']), 600);
        $this->assertTrue(Cache::has("forms:show:{$form->id}"));

        $repo->update($form, CreateFormDTO::fromArray([
            'name' => 'F1 v2',
            'is_active' => true,
            'fields' => [['key' => 'k', 'label' => 'L', 'type' => 'text']],
        ]));

        $this->assertFalse(Cache::has("forms:show:{$form->id}"));
        $this->assertFalse(Cache::has('forms:active:list'));
    }

    public function test_delete_busts_caches(): void
    {
        $admin = $this->makeUser(UserRole::Admin);
        $repo = $this->repo();
        $form = $repo->create(CreateFormDTO::fromArray([
            'name' => 'F',
            'is_active' => true,
            'fields' => [['key' => 'k', 'label' => 'L', 'type' => 'text']],
        ]), $admin->id);
        $repo->find($form->id);
        Cache::put('forms:active:list', collect(['stale']), 600);

        $repo->delete($form);

        $this->assertFalse(Cache::has("forms:show:{$form->id}"));
        $this->assertFalse(Cache::has('forms:active:list'));
    }

    public function test_implements_form_repository_interface(): void
    {
        $this->assertInstanceOf(FormRepositoryInterface::class, $this->repo());
    }
}
