<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class ValidaRfc implements ValidationRule
{
    /** Persona moral: 3-letter company fragment + 6-digit date + 3-char homoclave = 12 chars */
    private const REGEX_MORAL = '/^[A-ZÑ&]{3}[0-9]{6}[A-Z0-9]{3}$/u';

    /** Persona física: 4-letter surname fragment + 6-digit birth date + 3-char homoclave = 13 chars */
    private const REGEX_FISICA = '/^[A-ZÑ&]{4}[0-9]{6}[A-Z0-9]{3}$/u';

    /** Generic RFCs that are always valid per SAT rules */
    private const GENERICOS = ['XAXX010101000', 'XEXX010101000'];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $rfc = mb_strtoupper(mb_trim((string) $value));

        if (in_array($rfc, self::GENERICOS, strict: true)) {
            return;
        }

        if (! preg_match(self::REGEX_MORAL, $rfc) && ! preg_match(self::REGEX_FISICA, $rfc)) {
            $fail('El RFC no tiene el formato válido del SAT (12 caracteres persona moral, 13 caracteres persona física).');
        }
    }
}
