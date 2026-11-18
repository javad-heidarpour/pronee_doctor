<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinic_service_inventory_requirements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('service_id')->constrained('clinic_services')->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained('clinic_inventory_items')->restrictOnDelete();
            $table->decimal('quantity', 12, 3);
            $table->timestamps();
            $table->unique(['service_id', 'inventory_item_id'], 'service_inventory_requirement_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_service_inventory_requirements');
    }
};
