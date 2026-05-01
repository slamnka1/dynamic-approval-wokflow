<?php

namespace App\Domain\Enums;

enum WorkflowType: string
{
    case Sequential = 'sequential';
    case Threshold = 'threshold';

    public static function values(): array
    {
        return array_map(fn ($c) => $c->value, self::cases());
    }
}
