<?php

declare(strict_types=1);

namespace Tests\Unit\Payments;

use App\Actions\Payments\NormalizeSepVerificationResponseAction;
use App\Actions\Payments\ValidateAppointmentPaymentPhoneAction;
use App\Actions\Payments\ValidateSepCallbackTerminalAction;
use App\Models\Appointment;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

final class PaymentValidationActionsTest extends TestCase
{
    public function test_it_normalizes_sep_verification_fields(): void
    {
        $result = app(NormalizeSepVerificationResponseAction::class)->handle([
            'Success' => true,
            'ResultCode' => 0,
            'ResultDescription' => 'Verified',
            'TransactionDetail' => [
                'OrginalAmount' => 7000000,
                'RefNum' => '123456789',
                'TerminalNumber' => '12345678',
            ],
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame(0, $result['result_code']);
        $this->assertSame('Verified', $result['result_message']);
        $this->assertSame(7000000, $result['verified_amount']);
        $this->assertSame('123456789', $result['verified_ref_num']);
        $this->assertSame('12345678', $result['verified_terminal_number']);
    }

    public function test_it_rejects_malformed_sep_verification_responses(): void
    {
        $this->expectException(RuntimeException::class);

        app(NormalizeSepVerificationResponseAction::class)->handle([
            'Success' => true,
            'TransactionDetail' => [],
        ]);
    }

    #[DataProvider('validPhoneNumbers')]
    public function test_it_accepts_only_valid_iranian_mobile_numbers(string $phone): void
    {
        $appointment = new Appointment(['patient_phone' => $phone]);

        $result = app(ValidateAppointmentPaymentPhoneAction::class)->handle($appointment);

        $this->assertSame($phone, $result);
    }

    public static function validPhoneNumbers(): array
    {
        return [
            'mci' => ['09120000000'],
            'rightel' => ['09210000000'],
        ];
    }

    #[DataProvider('invalidPhoneNumbers')]
    public function test_it_rejects_invalid_mobile_numbers(string $phone): void
    {
        $appointment = new Appointment(['patient_phone' => $phone]);

        $this->expectException(RuntimeException::class);

        app(ValidateAppointmentPaymentPhoneAction::class)->handle($appointment);
    }

    public static function invalidPhoneNumbers(): array
    {
        return [
            'empty' => [''],
            'too short' => ['0912000000'],
            'wrong prefix' => ['08120000000'],
            'international format' => ['+989120000000'],
            'letters' => ['0912ABC0000'],
        ];
    }

    public function test_it_accepts_only_the_configured_sep_terminal(): void
    {
        config(['services.sep.terminal_id' => '12345678']);

        $action = app(ValidateSepCallbackTerminalAction::class);

        $this->assertTrue($action->handle('12345678'));
        $this->assertFalse($action->handle('87654321'));
        $this->assertFalse($action->handle(''));
    }

    public function test_it_rejects_all_callback_terminals_when_configuration_is_missing(): void
    {
        config(['services.sep.terminal_id' => '']);

        $this->assertFalse(
            app(ValidateSepCallbackTerminalAction::class)->handle('12345678')
        );
    }
}
