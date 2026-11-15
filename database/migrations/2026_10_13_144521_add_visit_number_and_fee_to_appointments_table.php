
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->unsignedBigInteger('visit_number')
                ->nullable()
                ->unique();

            // مبلغ به ریال
            $table->unsignedBigInteger('fee_rial')
                ->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropUnique(['visit_number']);
            $table->dropColumn(['visit_number', 'fee_rial']);
        });
    }
};