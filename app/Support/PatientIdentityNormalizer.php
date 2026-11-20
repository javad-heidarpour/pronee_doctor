<?php

declare(strict_types=1);

namespace App\Support;

final class PatientIdentityNormalizer
{
    public function normalizeName(?string $value): string
    {
        return trim((string) $value);
    }

    public function normalizePhone(?string $value): ?string
    {
        $digits = $this->digitsOnly($value);

        if (str_starts_with($digits, '0098') && strlen($digits) === 14) {
            $digits = '0' . substr($digits, 4);
        } elseif (str_starts_with($digits, '98') && strlen($digits) === 12) {
            $digits = '0' . substr($digits, 2);
        }

        return $digits === '' ? null : $digits;
    }

    public function normalizeNationalCode(?string $value): ?string
    {
        $digits = $this->digitsOnly($value);

        return $digits === '' ? null : $digits;
    }

    private function digitsOnly(?string $value): string
    {
        $normalized = strtr(trim((string) $value), [
            '۰' => '0',
            '۱' => '1',
            '۲' => '2',
            '۳' => '3',
            '۴' => '4',
            '۵' => '5',
            '۶' => '6',
            '۷' => '7',
            '۸' => '8',
            '۹' => '9',
            '٠' => '0',
            '١' => '1',
            '٢' => '2',
            '٣' => '3',
            '٤' => '4',
            '٥' => '5',
            '٦' => '6',
            '٧' => '7',
            '٨' => '8',
            '٩' => '9',
        ]);

        return preg_replace('/\D+/', '', $normalized) ?? '';
    }
}
