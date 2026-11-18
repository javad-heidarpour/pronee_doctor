<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\MedicalReferralWorkflow;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class MedicalReferralWorkflowTest extends TestCase
{
    public function test_recipient_can_progress_referral_through_valid_states(): void
    {
        $workflow = app(MedicalReferralWorkflow::class);
        $this->assertSame(['accepted', 'rejected'], $workflow->allowedTransitions('sent', false, true));
        $this->assertSame(['scheduled', 'in_progress', 'rejected'], $workflow->allowedTransitions('accepted', false, true));
        $this->assertSame(['in_progress', 'completed', 'rejected'], $workflow->allowedTransitions('scheduled', false, true));
        $this->assertSame(['completed', 'rejected'], $workflow->allowedTransitions('in_progress', false, true));
    }

    public function test_sender_can_only_cancel_an_open_referral(): void
    {
        $workflow = app(MedicalReferralWorkflow::class);
        $this->assertSame(['cancelled'], $workflow->allowedTransitions('sent', true, false));
        $this->assertSame(['cancelled'], $workflow->allowedTransitions('scheduled', true, false));
        $this->assertSame([], $workflow->allowedTransitions('completed', true, false));
    }

    public function test_unrelated_user_cannot_change_referral_state(): void
    {
        $workflow = app(MedicalReferralWorkflow::class);
        $this->assertSame([], $workflow->allowedTransitions('sent', false, false));
        $this->expectException(ValidationException::class);
        $workflow->assertCanTransition('sent', 'accepted', false, false);
    }

    public function test_completed_referral_cannot_be_reopened(): void
    {
        $this->assertSame([], app(MedicalReferralWorkflow::class)->allowedTransitions('completed', false, true));
    }
}
