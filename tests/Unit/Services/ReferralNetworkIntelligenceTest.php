<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\ReferralNetworkIntelligence;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class ReferralNetworkIntelligenceTest extends TestCase
{
    protected function tearDown(): void
    {
        Schema::dropIfExists('medical_referrals');

        parent::tearDown();
    }

    public function test_it_declares_a_bounded_reporting_window_and_minimum_sample(): void
    {
        $this->assertSame(180, ReferralNetworkIntelligence::WINDOW_DAYS);
        $this->assertSame(5, ReferralNetworkIntelligence::MINIMUM_SAMPLE);
    }

    public function test_empty_provider_set_returns_no_patient_level_data(): void
    {
        $result = (new ReferralNetworkIntelligence())->forProviders(collect());

        $this->assertSame(180, $result['window_days']);
        $this->assertSame(5, $result['minimum_sample']);
        $this->assertSame([], $result['providers']);
    }
    public function test_average_response_time_is_hidden_below_the_minimum_sample(): void
    {
        Schema::create('medical_referrals', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('target_provider_id');
            $table->string('status', 24);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();
        });

        $now = Carbon::parse('2026-11-21 12:00:00');
        DB::table('medical_referrals')->insert([
            'target_provider_id' => 9,
            'status' => 'accepted',
            'created_at' => $now->copy()->subHours(4),
            'accepted_at' => $now->copy()->subHours(2),
        ]);

        $provider = (object) ['id' => 9, 'name' => 'Provider', 'provider_type' => 'laboratory', 'city' => 'Bojnord'];
        $result = (new ReferralNetworkIntelligence())->forProviders(collect([$provider]), $now);

        $this->assertSame(1, $result['providers'][0]['sample_size']);
        $this->assertFalse($result['providers'][0]['data_sufficient']);
        $this->assertNull($result['providers'][0]['average_response_hours']);
        $this->assertNull($result['providers'][0]['acceptance_rate']);
        $this->assertNull($result['providers'][0]['completion_rate']);

        foreach (range(1, 4) as $offset) {
            DB::table('medical_referrals')->insert([
                'target_provider_id' => 9,
                'status' => 'accepted',
                'created_at' => $now->copy()->subHours(4 + $offset),
                'accepted_at' => $now->copy()->subHours(2 + $offset),
            ]);
        }

        $sufficient = (new ReferralNetworkIntelligence())->forProviders(collect([$provider]), $now);
        $this->assertSame(5, $sufficient['providers'][0]['sample_size']);
        $this->assertNotNull($sufficient['providers'][0]['average_response_hours']);
    }

}
