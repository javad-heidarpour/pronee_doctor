<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('clinic_suppliers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->foreignId('clinic_id')->nullable()->constrained('addresses')->nullOnDelete();
            $table->string('name', 180);
            $table->string('contact_name', 120)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('email')->nullable();
            $table->string('tax_id', 80)->nullable();
            $table->string('address', 500)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['doctor_id', 'is_active']);
        });

        Schema::create('clinic_supplier_quotes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->foreignId('clinic_id')->constrained('addresses')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('clinic_suppliers')->restrictOnDelete();
            $table->foreignId('inventory_item_id')->constrained('clinic_inventory_items')->restrictOnDelete();
            $table->decimal('minimum_quantity', 12, 3)->unsigned()->default(1);
            $table->unsignedBigInteger('unit_cost_rial');
            $table->unsignedSmallInteger('lead_time_days')->default(0);
            $table->date('valid_until')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['clinic_id', 'inventory_item_id', 'is_active'], 'supplier_quote_compare_idx');
        });

        Schema::create('clinic_purchase_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->foreignId('clinic_id')->constrained('addresses')->restrictOnDelete();
            $table->foreignId('supplier_id')->constrained('clinic_suppliers')->restrictOnDelete();
            $table->foreignId('quote_id')->nullable()->constrained('clinic_supplier_quotes')->nullOnDelete();
            $table->string('order_number', 40)->unique();
            $table->string('status', 24)->default('draft');
            $table->unsignedBigInteger('total_amount_rial')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('received_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['doctor_id', 'clinic_id', 'status']);
        });

        Schema::create('clinic_purchase_order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('clinic_purchase_orders')->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained('clinic_inventory_items')->restrictOnDelete();
            $table->decimal('ordered_quantity', 12, 3)->unsigned();
            $table->decimal('received_quantity', 12, 3)->unsigned()->default(0);
            $table->unsignedBigInteger('unit_cost_rial');
            $table->unsignedBigInteger('line_total_rial');
            $table->timestamps();
            $table->index(['purchase_order_id', 'inventory_item_id']);
        });

        if (Schema::hasTable('menus')) {
            DB::table('menus')->updateOrInsert(
                ['route_name' => 'portal.procurement.index'],
                ['title' => 'تأمین‌کنندگان و خرید', 'icon' => 'IconTruck', 'order' => 59,
                    'guard' => 'doctor', 'is_active' => true, 'show_in_sidebar' => true,
                    'permissions' => json_encode(['inventory.view']), 'roles' => json_encode(['doctor','admin','secretary']),
                    'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('menus')) DB::table('menus')->where('route_name', 'portal.procurement.index')->delete();
        Schema::dropIfExists('clinic_purchase_order_items');
        Schema::dropIfExists('clinic_purchase_orders');
        Schema::dropIfExists('clinic_supplier_quotes');
        Schema::dropIfExists('clinic_suppliers');
    }
};
