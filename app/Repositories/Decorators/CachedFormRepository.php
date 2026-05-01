<?php

namespace App\Repositories\Decorators;

use App\Domain\Contracts\FormRepositoryInterface;
use App\Domain\DTOs\CreateFormDTO;
use App\Models\Form;
use App\Support\FormCache;
use Illuminate\Support\Collection;

class CachedFormRepository implements FormRepositoryInterface
{
    public function __construct(
        private readonly FormRepositoryInterface $inner,
        private readonly FormCache $cache,
    ) {}

    public function allForAdmin(): Collection
    {
        return $this->inner->allForAdmin();
    }

    public function listActive(): Collection
    {
        return $this->inner->listActive();
    }

    public function find(int $id): ?Form
    {
        if ($cached = $this->cache->get($id)) {
            return $cached;
        }

        $form = $this->inner->find($id);

        if ($form) {
            $this->cache->put($id, $form);
        }

        return $form;
    }

    public function create(CreateFormDTO $dto, int $creatorId): Form
    {
        $created = $this->inner->create($dto, $creatorId);
        $this->refresh($created->id);

        return $created;
    }

    public function update(Form $form, CreateFormDTO $dto): Form
    {
        $updated = $this->inner->update($form, $dto);
        $this->refresh($updated->id);

        return $updated;
    }

    public function delete(Form $form): void
    {
        $id = $form->id;
        $this->inner->delete($form);
        $this->cache->forget($id);
    }

    /**
     * Re-fetch the form fully eager-loaded and write it through.
     */
    private function refresh(int $formId): void
    {
        $this->cache->forget($formId);

        $fresh = $this->inner->find($formId);

        if ($fresh) {
            $this->cache->put($formId, $fresh);
        }
    }
}
