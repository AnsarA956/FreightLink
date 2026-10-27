<?php

namespace App\Services;

use App\Models\DiscountCode;
use RuntimeException;

class DiscountService
{
    // Turns the text typed by the customer into a valid code, or throws a clear message.
    // Returns null when nothing was typed.
    public function resolve(?string $input): ?DiscountCode
    {
        $input = strtoupper(trim((string) $input));

        if ($input === '') {
            return null;
        }

        $code = DiscountCode::where('code', $input)->first();

        if (! $code) {
            throw new RuntimeException('The discount code "' . $input . '" does not exist.');
        }

        if (! $code->isUsable()) {
            throw new RuntimeException('The discount code "' . $input . '" has expired or is no longer active.');
        }

        return $code;
    }
}