<?php

declare(strict_types=1);

namespace Tests\Unit\Portal\Fees;

use App\Actions\Portal\Fees\UpdateFeeAction;
use App\Models\Fee;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class UpdateFeeActionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('doctors', function (Blueprint $table): void { $table->id(); $table->softDeletes(); $table->timestamps(); });
        Schema::create('fees', function (Blueprint $table): void {
            $table->id(); $table->string('title'); $table->unsignedBigInteger('fee_rial');
            $table->boolean('is_active')->default(true); $table->unsignedBigInteger('created_by');
            $table->timestamp('deactivated_at')->nullable(); $table->timestamps();
        });
        Schema::create('appointments', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('doctor_id'); $table->string('status');
            $table->unsignedBigInteger('fee_rial'); $table->timestamps();
        });
        Schema::create('appointment_payments', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('appointment_id');
            $table->unsignedBigInteger('amount_rial'); $table->string('status'); $table->timestamps();
        });
    }

    public function test_fee_change_versions_tariff_and_reprices_only_unpaid_pending_appointments(): void
    {
        $doctorId = DB::table('doctors')->insertGetId(['created_at'=>now(),'updated_at'=>now()]);
        $oldFeeId = DB::table('fees')->insertGetId([
            'title'=>'تعرفه ویزیت','fee_rial'=>5000000,'is_active'=>true,'created_by'=>$doctorId,'created_at'=>now(),'updated_at'=>now(),
        ]);
        $unpaidId = DB::table('appointments')->insertGetId([
            'doctor_id'=>$doctorId,'status'=>'pending','fee_rial'=>5000000,'created_at'=>now(),'updated_at'=>now(),
        ]);
        $inFlightId = DB::table('appointments')->insertGetId([
            'doctor_id'=>$doctorId,'status'=>'pending','fee_rial'=>5000000,'created_at'=>now(),'updated_at'=>now(),
        ]);
        DB::table('appointment_payments')->insert([
            'appointment_id'=>$inFlightId,'amount_rial'=>5000000,'status'=>'initiated','created_at'=>now(),'updated_at'=>now(),
        ]);

        $changed = app(UpdateFeeAction::class)->handle(6500000,$doctorId);

        $this->assertTrue($changed);
        $this->assertDatabaseHas('fees',['id'=>$oldFeeId,'is_active'=>false]);
        $this->assertDatabaseHas('fees',['created_by'=>$doctorId,'fee_rial'=>6500000,'is_active'=>true]);
        $this->assertDatabaseHas('appointments',['id'=>$unpaidId,'fee_rial'=>6500000]);
        $this->assertDatabaseHas('appointments',['id'=>$inFlightId,'fee_rial'=>5000000]);
    }

    public function test_repeating_same_fee_does_not_create_duplicate_active_version(): void
    {
        $doctorId = DB::table('doctors')->insertGetId(['created_at'=>now(),'updated_at'=>now()]);
        Fee::query()->create(['title'=>'تعرفه ویزیت','fee_rial'=>6500000,'is_active'=>true,'created_by'=>$doctorId]);

        $changed = app(UpdateFeeAction::class)->handle(6500000,$doctorId);

        $this->assertFalse($changed);
        $this->assertSame(1,Fee::query()->where('created_by',$doctorId)->where('is_active',true)->count());
        $this->assertSame(1,Fee::query()->where('created_by',$doctorId)->count());
    }

    public function test_fee_change_is_scoped_to_selected_doctor(): void
    {
        $doctorA = DB::table('doctors')->insertGetId(['created_at'=>now(),'updated_at'=>now()]);
        $doctorB = DB::table('doctors')->insertGetId(['created_at'=>now(),'updated_at'=>now()]);
        Fee::query()->create(['title'=>'تعرفه A','fee_rial'=>4000000,'is_active'=>true,'created_by'=>$doctorA]);
        Fee::query()->create(['title'=>'تعرفه B','fee_rial'=>3000000,'is_active'=>true,'created_by'=>$doctorB]);

        app(UpdateFeeAction::class)->handle(7000000,$doctorA);

        $this->assertDatabaseHas('fees',['created_by'=>$doctorB,'fee_rial'=>3000000,'is_active'=>true]);
        $this->assertDatabaseHas('fees',['created_by'=>$doctorA,'fee_rial'=>7000000,'is_active'=>true]);
    }
}
