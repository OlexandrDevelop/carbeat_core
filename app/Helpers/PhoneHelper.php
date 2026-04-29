<?php

namespace App\Helpers;

class PhoneHelper
{
    /**
     * Normalize a phone number to E.164 format.
     *
     * @param  string  $defaultCountry  ISO 3166-1 alpha-2 country code used when the number
     *                                  has no international prefix (e.g. "UA", "DE").
     */
    public function normalize(string $phone, string $defaultCountry = 'UA'): string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        // Already carries an international prefix other than Ukraine → keep as-is.
        // E.g. "+49 2389 12345" → digits "492389..." not starting with "380".
        if (str_starts_with(ltrim($phone), '+') && ! str_starts_with($digits, '380')) {
            return '+' . $digits;
        }

        // 0049… → +49…
        if (str_starts_with($digits, '0049')) {
            return '+49' . substr($digits, 4);
        }

        // German local numbers when defaultCountry is DE
        if ($defaultCountry === 'DE') {
            if (str_starts_with($digits, '49')) {
                return '+' . $digits;
            }
            // Local format: 0XXXXXXXXXX (10–11 digits)
            if (str_starts_with($digits, '0') && strlen($digits) >= 10) {
                return '+49' . substr($digits, 1);
            }
        }

        // Ukrainian normalization
        if (str_starts_with($digits, '380')) {
            return '+' . $digits;
        }
        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            return '+380' . substr($digits, 1);
        }
        if (strlen($digits) === 9) {
            return '+380' . $digits;
        }

        return '+' . $digits;
    }

    /**
     * Returns true for mobile numbers, false for landlines.
     * Supports Ukrainian (+380) and German (+49) numbers.
     * Returns false for unknown country codes.
     */
    public static function isMobile(string $phone): bool
    {
        $digits = preg_replace('/\D+/', '', $phone);

        // Ukrainian numbers
        if (str_starts_with($digits, '380')) {
            $operator = substr($digits, 3, 2);
        } elseif (str_starts_with($digits, '0') && strlen($digits) === 10) {
            $operator = substr($digits, 1, 2);
        } elseif (str_starts_with($digits, '49')) {
            // German mobile: after +49, number starts with 15x, 16x, or 17x
            $afterPrefix = substr($digits, 2);
            return strlen($afterPrefix) >= 10
                && in_array(substr($afterPrefix, 0, 2), ['15', '16', '17'], true);
        } else {
            return false;
        }

        // Ukrainian mobile operator codes
        $mobileCodes = [
            '50', '63', '66', '67', '68',
            '73', '91', '92', '93', '94',
            '95', '96', '97', '98', '99',
        ];

        return in_array($operator, $mobileCodes, true);
    }
}
