<?php

declare(strict_types=1);

namespace Tests\Unit\Payments;

use App\Actions\Payments\ValidateSepCallbackTerminalAction;
use App\Http\Requests\Portal\ListPaymentReviewRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

final class PaymentReviewAuthorizationTest extends TestCase
{
    public function test_only_admin_can_authorize_the_cross_clinic_payment_review_queue(): void
    {
        foreach (['doctor', 'secretary', 'user'] as $role) {
            Auth::guard('web')->setUser(new User(['role' => $role]));
            $this->assertFalse((new ListPaymentReviewRequest())->authorize(), $role.' must not inspect the global payment queue.');
        }

        Auth::guard('web')->setUser(new User(['role' => 'admin']));
        $this->assertTrue((new ListPaymentReviewRequest())->authorize());
    }

    public function test_sep_callback_terminal_must_match_configured_terminal_exactly(): void
    {
        $action = new ValidateSepCallbackTerminalAction();

        config(['services.sep.terminal_id' => '12345678']);
        $this->assertTrue($action->handle('12345678'));
        $this->assertFalse($action->handle('87654321'));
        $this->assertFalse($action->handle(''));
        $this->assertFalse($action->handle(' 12345678'));

        config(['services.sep.terminal_id' => '']);
        $this->assertFalse($action->handle('12345678'));
    }
}
