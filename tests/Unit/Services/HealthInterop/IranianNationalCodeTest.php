<?php

declare(strict_types=1);

namespace Tests\Unit\Services\HealthInterop;

use App\Services\HealthInterop\IranianNationalCode;
use PHPUnit\Framework\TestCase;

final class IranianNationalCodeTest extends TestCase
{
    public function test_it_normalizes_persian_and_arabic_digits(): void
    {
        self::assertSame('0123456789', IranianNationalCode::normalize('۰۱۲۳۴۵۶۷۸۹'));
        self::assertSame('0123456789', IranianNationalCode::normalize('٠١٢٣٤٥٦٧٨٩'));
    }

    public function test_it_rejects_repeated_or_malformed_codes(): void
    {
        self::assertFalse(IranianNationalCode::isValid('1111111111'));
        self::assertFalse(IranianNationalCode::isValid('123'));
        self::assertFalse(IranianNationalCode::isValid('abcdefghij'));
    }

    public function test_it_accepts_a_code_with_a_correct_check_digit(): void
    {
        $base = '123456789';
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += ((int) $base[$i]) * (10 - $i);
        }
        $remainder = $sum % 11;
        $digit = $remainder < 2 ? $remainder : 11 - $remainder;
        self::assertTrue(IranianNationalCode::isValid($base.$digit));
    }
}
