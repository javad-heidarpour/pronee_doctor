<?php
declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  if (!Schema::hasTable('menus')) return;
  DB::table('menus')->updateOrInsert(['route_name'=>'portal.patient-retention.index'],[
   'title'=>'حفظ و بازگشت بیماران','icon'=>'IconHeartHandshake','order'=>60,'guard'=>'doctor',
   'is_active'=>true,'show_in_sidebar'=>true,'permissions'=>json_encode(['patients.view']),
   'roles'=>json_encode(['doctor','admin','secretary']),'created_at'=>now(),'updated_at'=>now(),
  ]);
 }
 public function down(): void {
  if (Schema::hasTable('menus')) DB::table('menus')->where('route_name','portal.patient-retention.index')->delete();
 }
};