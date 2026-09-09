<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class Sheba implements ValidationRule
{
    private const int LENGTH = 26;

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || preg_match('/^IR[0-9]{'.(self::LENGTH - 2).'}$/', $value) !== 1) {
            $fail('validation.sheba')->translate();

            return;
        }

        if ($this->remainder($value) !== 1) {
            $fail('validation.sheba')->translate();
        }
    }

    private function remainder(string $sheba): int
    {
        $rearranged = substr($sheba, 4).substr($sheba, 0, 4);

        $digits = '';

        foreach (str_split($rearranged) as $character) {
            $digits .= ctype_digit($character)
                ? $character
                : (string) (ord($character) - ord('A') + 10);
        }

        $remainder = 0;

        foreach (str_split($digits, 7) as $chunk) {
            $remainder = ((int) ($remainder.$chunk)) % 97;
        }

        return $remainder;
    }
}
