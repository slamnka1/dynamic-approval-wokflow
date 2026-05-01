<?php

namespace App\Support;

use App\Models\Form;
use Illuminate\Contracts\Cache\Repository as Cache;

class FormCache
{
    public const TTL = 600;

    public function __construct(
        private readonly Cache $cache,
    ) {}

    public function key(int $formId): string
    {
        return "forms:show:{$formId}";
    }

    public function get(int $formId): ?Form
    {
        $value = $this->cache->get($this->key($formId));

        return $value instanceof Form ? $value : null;
    }

    public function put(int $formId, Form $form): void
    {
        $this->cache->put($this->key($formId), $form, self::TTL);
    }

    public function forget(int $formId): void
    {
        $this->cache->forget($this->key($formId));
    }

    public function flushAll(): void
    {
        $this->cache->flush();
    }
}
