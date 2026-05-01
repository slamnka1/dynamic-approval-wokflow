<?php

namespace App\Validation;

use App\Domain\Contracts\DynamicFieldValidatorInterface;
use App\Domain\Enums\FieldType;
use App\Models\Form;
use App\Models\FormField;
use Illuminate\Support\Facades\Validator;

class DynamicFieldValidator implements DynamicFieldValidatorInterface
{
    public function validate(Form $form, array $data, array $files = []): array
    {
        $rules = [];
        $messages = [];
        $payload = [];

        $form->loadMissing(['fields.options']);

        foreach ($form->fields as $field) {
            $key = "values.{$field->key}";
            $value = $data[$field->key] ?? ($files[$field->key] ?? null);
            $payload['values'][$field->key] = $value;
            $rules[$key] = $this->rulesFor($field);
            $messages["{$key}.required"] = "The {$field->label} field is required.";
        }

        $validated = Validator::make($payload, $rules, $messages)->validate();

        return $validated['values'] ?? [];
    }

    /**
     * @return array<int, mixed>
     */
    private function rulesFor(FormField $field): array
    {
        $rules = [$field->is_required ? 'required' : 'nullable'];

        switch ($field->type) {
            case FieldType::Text:
            case FieldType::Textarea:
                $rules[] = 'string';
                $rules[] = 'max:'.($field->type === FieldType::Textarea ? '5000' : '255');
                break;

            case FieldType::Number:
                $rules[] = 'numeric';
                if ($field->min_value !== null) {
                    $rules[] = 'min:'.$field->min_value;
                }
                if ($field->max_value !== null) {
                    $rules[] = 'max:'.$field->max_value;
                }
                break;

            case FieldType::Date:
                $rules[] = 'date';
                break;

            case FieldType::Checkbox:
                $rules[] = 'boolean';
                break;

            case FieldType::Select:
                $values = $field->options->pluck('value')->all();
                $rules[] = \Illuminate\Validation\Rule::in($values);
                break;

            case FieldType::File:
                $rules[] = 'file';
                $rules[] = 'max:5120';
                break;
        }

        return $rules;
    }
}
