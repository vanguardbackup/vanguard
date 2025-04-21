<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Override;

class ValidHexColour implements ValidationRule
{
    /**
     * Determine if the validation rule passes.
     */
    #[Override]
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The :attribute must be a string.');

            return;
        }

        $value = trim($value);

        $pattern = '/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/';

        if (! preg_match($pattern, $value)) {
            $fail('The :attribute must be a valid hex color code (e.g., #d37445 or #f60).');
        }
    }
}
