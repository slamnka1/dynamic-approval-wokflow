<?php

namespace Tests\Unit;

use App\Domain\Contracts\FormRepositoryInterface;
use App\Domain\DTOs\CreateFormDTO;
use App\Domain\Enums\UserRole;
use App\Models\Form;
use App\Repositories\Decorators\CachedFormRepository;
use App\Repositories\EloquentFormRepository;
use App\Support\FormCache;
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
            app(FormCache::class),
        );
    }

    public function test_list_active_is_not_cached(): void
    {
        $admin = $this->makeUser(UserRole::Admin);
        $repo = $this->repo();

        $repo->create(CreateFormDTO::fromArray([
            'name' => 'F1',
            'is_active' => true,
            'fields' => [['key' => 'k', 'label' => 'L', 'type' => 'text']],
        ]), $admin->id);

        $this->assertCount(1, $repo->listActive());

        Form::query()->update(['is_active' => false]);

        $this->assertCount(0, $repo->listActive());
    }

    public function test_create_writes_through_to_cache(): void
    {
        $admin = $this->makeUser(UserRole::Admin);
        $repo = $this->repo();
        $cache = app(FormCache::class);

        $form = $repo->create(CreateFormDTO::fromArray([
            'name' => 'F1',
            'is_active' => true,
            'fields' => [['key' => 'k', 'label' => 'L', 'type' => 'text']],
        ]), $admin->id);

        $cached = $cache->get($form->id);

        $this->assertNotNull($cached);
        $this->assertSame('F1', $cached->name);
        $this->assertCount(1, $cached->fields);
    }

    public function test_find_caches_individual_form(): void
    {
        $admin = $this->makeUser(UserRole::Admin);
        $repo = $this->repo();
        $cache = app(FormCache::class);

        $form = $repo->create(CreateFormDTO::fromArray([
            'name' => 'F1',
            'is_active' => true,
            'fields' => [['key' => 'k', 'label' => 'L', 'type' => 'text']],
        ]), $admin->id);

        $cache->forget($form->id);
        $this->assertNull($cache->get($form->id));

        $repo->find($form->id);

        $this->assertNotNull($cache->get($form->id));
    }

    public function test_update_refreshes_cache_with_new_value(): void
    {
        $admin = $this->makeUser(UserRole::Admin);
        $repo = $this->repo();
        $cache = app(FormCache::class);

        $form = $repo->create(CreateFormDTO::fromArray([
            'name' => 'Original',
            'is_active' => true,
            'fields' => [['key' => 'k', 'label' => 'L', 'type' => 'text']],
        ]), $admin->id);

        $repo->update($form, CreateFormDTO::fromArray([
            'name' => 'Renamed',
            'is_active' => true,
            'fields' => [['key' => 'k', 'label' => 'L', 'type' => 'text']],
        ]));

        $cached = $cache->get($form->id);

        $this->assertNotNull($cached);
        $this->assertSame('Renamed', $cached->name);
    }

    public function test_delete_forgets_form_cache(): void
    {
        $admin = $this->makeUser(UserRole::Admin);
        $repo = $this->repo();
        $cache = app(FormCache::class);

        $form = $repo->create(CreateFormDTO::fromArray([
            'name' => 'F',
            'is_active' => true,
            'fields' => [['key' => 'k', 'label' => 'L', 'type' => 'text']],
        ]), $admin->id);

        $this->assertNotNull($cache->get($form->id));

        $repo->delete($form);

        $this->assertNull($cache->get($form->id));
    }

    public function test_observer_busts_cache_on_external_form_change(): void
    {
        $admin = $this->makeUser(UserRole::Admin);
        $repo = $this->repo();
        $cache = app(FormCache::class);

        $form = $repo->create(CreateFormDTO::fromArray([
            'name' => 'F',
            'is_active' => true,
            'fields' => [['key' => 'k', 'label' => 'L', 'type' => 'text']],
        ]), $admin->id);

        $this->assertNotNull($cache->get($form->id));

        $form->update(['is_active' => false]);

        $this->assertNull($cache->get($form->id));
    }

    public function test_observer_busts_cache_on_field_change(): void
    {
        $admin = $this->makeUser(UserRole::Admin);
        $repo = $this->repo();
        $cache = app(FormCache::class);

        $form = $repo->create(CreateFormDTO::fromArray([
            'name' => 'F',
            'is_active' => true,
            'fields' => [['key' => 'k', 'label' => 'L', 'type' => 'text']],
        ]), $admin->id);

        $this->assertNotNull($cache->get($form->id));

        $form->fields()->first()->update(['label' => 'New Label']);

        $this->assertNull($cache->get($form->id));
    }

    public function test_implements_form_repository_interface(): void
    {
        $this->assertInstanceOf(FormRepositoryInterface::class, $this->repo());
    }
}
