<?php

namespace App\Domain\Enums;

enum FieldType: string
{
    case Text = 'text';
    case Textarea = 'textarea';
    case Number = 'number';
    case Select = 'select';
    case Date = 'date';
    case Checkbox = 'checkbox';
    case File = 'file';

    public static function values(): array
    {
        return array_map(fn ($c) => $c->value, self::cases());
    }
}
