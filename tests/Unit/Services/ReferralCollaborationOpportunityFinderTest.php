<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\ReferralCollaborationOpportunityFinder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class ReferralCollaborationOpportunityFinderTest extends TestCase
{
    public function test_service_types_map_to_the_matching_network_provider_type(): void
    {
        $finder = new ReferralCollaborationOpportunityFinder();

        $this->assertSame('laboratory', $finder->providerTypeForService('laboratory'));
        $this->assertSame('imaging', $finder->providerTypeForService('imaging'));
        $this->assertSame('rehabilitation', $finder->providerTypeForService('rehabilitation'));
        $this->assertSame('doctor', $finder->providerTypeForService('consultation'));
    }

    public function test_unknown_service_type_is_not_matched_to_a_provider(): void
    {
        $this->assertNull((new ReferralCollaborationOpportunityFinder())->providerTypeForService('unknown'));
    }

    public function test_referral_demand_is_limited_to_accessible_clinics(): void
    {
        Schema::create('medical_referrals', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('referring_doctor_id');
            $table->unsignedBigInteger('source_clinic_id');
            $table->unsignedBigInteger('target_provider_id')->nullable();
            $table->string('service_type', 32);
            $table->string('status', 20);
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('medical_network_providers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->string('provider_type', 32);
            $table->string('name');
            $table->string('status', 20);
            $table->boolean('is_verified')->default(false);
            $table->string('city')->nullable();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->json('specialties')->nullable();
            $table->json('services')->nullable();
            $table->timestamps();
        });

        $now = Carbon::parse('2026-11-19 08:00:00');
        DB::table('medical_network_providers')->insert([
            'id' => 9,
            'doctor_id' => 22,
            'created_by_user_id' => 22,
            'provider_type' => 'imaging',
            'name' => 'Imaging Center',
            'status' => 'active',
            'is_verified' => true,
            'city' => 'Bojnord',
            'specialties' => '[]',
            'services' => '[]',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('medical_referrals')->insert([
            'id' => 1,
            'referring_doctor_id' => 11,
            'source_clinic_id' => 200,
            'target_provider_id' => 9,
            'service_type' => 'imaging',
            'status' => 'sent',
            'accepted_at' => $now->copy()->subDays(1),
            'created_at' => $now->copy()->subDays(2),
            'updated_at' => $now->copy()->subDays(2),
        ]);

        $finder = new ReferralCollaborationOpportunityFinder();
        $hidden = $finder->find(11, 11, 'Bojnord', $now, [100]);
        $visible = $finder->find(11, 11, 'Bojnord', $now, [200]);

        $this->assertSame([], $hidden['items']);
        $this->assertCount(1, $visible['items']);
        $this->assertSame('imaging', $visible['items'][0]['service_type']);
        $this->assertSame(1, $visible['items'][0]['opportunities'][0]['history_sample']);
        $this->assertNull($visible['items'][0]['opportunities'][0]['average_response_hours']);
    }
}
