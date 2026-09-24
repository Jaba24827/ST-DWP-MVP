<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * The single expression of the password policy. Every place that accepts a
 * password — registration, change, reset — uses this rule, and the React
 * strength meter uses the same pattern so the two never disagree.
 *
 * Twelve characters or more, mixing small letters, capitals, numbers and
 * at least one symbol.
 */
class StrongPassword implements ValidationRule
{
    public const PATTERN = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9\s]).{12,}$/';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match(self::PATTERN, $value)) {
            $fail('The password must be at least 12 characters and mix capitals, small letters, numbers and a symbol.');
        }
    }

    /** Exposed to the frontend through a config endpoint so the rule lives in one place. */
    public static function jsPattern(): string
    {
        return trim(self::PATTERN, '/');
    }
}
