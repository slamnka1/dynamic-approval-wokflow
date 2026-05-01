<?php

namespace App\Domain\Contracts;

use App\Models\Form;

interface DynamicFieldValidatorInterface
{
    /**
     * Validates user-submitted data against a form's dynamic field schema.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed> the validated data, keyed by field key
     */
    public function validate(Form $form, array $data, array $files = []): array;
}
