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
        $normalized = strtr(
            trim((string) $value),
            '۰۱۲۳۴۵۶۷۸۹٠١٢٣٤٥٦٧٨٩',
            '01234567890123456789'
        );

        return preg_replace('/\D+/', '', $normalized) ?? '';
    }
}
