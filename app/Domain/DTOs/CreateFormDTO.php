<?php

namespace App\Domain\DTOs;

class CreateFormDTO
{
    /**
     * @param  array<int, array{key:string,label:string,type:string,is_required?:bool,sort_order?:int,min_value?:int|null,max_value?:int|null,placeholder?:string|null,options?:array<int, array{value:string,label:string,sort_order?:int}>}>  $fields
     */
    public function __construct(
        public readonly string $name,
        public readonly ?string $description,
        public readonly bool $isActive,
        public readonly array $fields,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            description: $data['description'] ?? null,
            isActive: (bool) ($data['is_active'] ?? true),
            fields: $data['fields'] ?? [],
        );
    }
}
