<?php

namespace App\Repositories\Decorators;

use App\Domain\Contracts\FormRepositoryInterface;
use App\Domain\DTOs\CreateFormDTO;
use App\Models\Form;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Collection;

class CachedFormRepository implements FormRepositoryInterface
{
    private const TTL = 600;

    private const KEY_ACTIVE_LIST = 'forms:active:list';

    private const KEY_SHOW = 'forms:show:';

    public function __construct(
        private readonly FormRepositoryInterface $inner,
        private readonly Cache $cache,
    ) {}

    public function allForAdmin(): Collection
    {
        return $this->inner->allForAdmin();
    }

    public function listActive(): Collection
    {
        return $this->cache->remember(
            self::KEY_ACTIVE_LIST,
            self::TTL,
            fn () => $this->inner->listActive(),
        );
    }

    public function find(int $id): ?Form
    {
        return $this->cache->remember(
            self::KEY_SHOW.$id,
            self::TTL,
            fn () => $this->inner->find($id),
        );
    }

    public function create(CreateFormDTO $dto, int $creatorId): Form
    {
        $form = $this->inner->create($dto, $creatorId);
        $this->bust($form->id);

        return $form;
    }

    public function update(Form $form, CreateFormDTO $dto): Form
    {
        $updated = $this->inner->update($form, $dto);
        $this->bust($updated->id);

        return $updated;
    }

    public function delete(Form $form): void
    {
        $id = $form->id;
        $this->inner->delete($form);
        $this->bust($id);
    }

    private function bust(int $formId): void
    {
        $this->cache->forget(self::KEY_ACTIVE_LIST);
        $this->cache->forget(self::KEY_SHOW.$formId);
    }
}
