<?php

declare(strict_types=1);

namespace Tests\\Unit\\Models;

use App\\Models\\MedicalCollaborationContract;
use App\\Models\\MedicalReferral;
use Illuminate\\Database\\Eloquent\\Relations\\HasMany;
use Illuminate\\Database\\Eloquent\\Relations\\HasOne;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Schema;
use Tests\\TestCase;

final class MedicalReferralTest extends TestCase
{
    public function test_for_clinics_scope_only_returns_referrals_from_accessible_clinics(): void
    {
        Schema::create('medical_referrals', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('referring_doctor_id');
            $table->unsignedBigInteger('source_clinic_id');
            $table->timestamps();
        });

        $allowedId = DB::table('medical_referrals')->insertGetId([
            'referring_doctor_id' => 5,
            'source_clinic_id' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('medical_referrals')->insert([
            'referring_doctor_id' => 5,
            'source_clinic_id' => 11,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $visible = MedicalReferral::query()
            ->where('referring_doctor_id', 5)
            ->forClinics([10])
            ->pluck('id')
            ->all();

        $this->assertSame([$allowedId], $visible);
        $this->assertSame([], MedicalReferral::query()->forClinics([])->pluck('id')->all());
    }

    public function test_referral_and_contract_have_the_expected_settlement_relations(): void
    {
        $settlement = (new MedicalReferral())->settlement();
        $this->assertInstanceOf(HasOne::class, $settlement);
        $this->assertSame('referral_id', $settlement->getForeignKeyName());

        $settlements = (new MedicalCollaborationContract())->settlements();
        $this->assertInstanceOf(HasMany::class, $settlements);
        $this->assertSame('contract_id', $settlements->getForeignKeyName());
    }
}
