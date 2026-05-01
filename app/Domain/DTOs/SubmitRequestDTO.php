<?php

namespace App\Domain\DTOs;

class SubmitRequestDTO
{
    /**
     * @param  array<string, mixed>  $values  keyed by field key
     */
    public function __construct(
        public readonly array $values,
    ) {}
}
