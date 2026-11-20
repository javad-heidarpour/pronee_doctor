<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Address;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class AddressSoftDeletesTest extends TestCase
{
    private string $connection = 'address_soft_delete_test';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.connections.' . $this->connection => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);

        DB::purge($this->connection);

        Schema::connection($this->connection)->create('addresses', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        DB::purge($this->connection);

        parent::tearDown();
    }

    public function test_deleting_a_clinic_keeps_its_database_row_and_hides_it_from_normal_queries(): void
    {
        $clinic = Address::on($this->connection)->create([
            'name' => 'مطب آزمایشی',
        ]);

        $clinic->delete();

        $this->assertTrue(
            DB::connection($this->connection)->table('addresses')
                ->where('id', $clinic->id)
                ->whereNotNull('deleted_at')
                ->exists()
        );

        $this->assertFalse(
            Address::on($this->connection)->whereKey($clinic->id)->exists()
        );

        $this->assertTrue(
            Address::on($this->connection)->withTrashed()->whereKey($clinic->id)->exists()
        );
    }
}
