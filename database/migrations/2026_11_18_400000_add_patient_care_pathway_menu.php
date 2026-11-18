<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('menus')) return;
        if (!DB::table('menus')->where('route_name','portal.care-pathways.index')->exists()) {
            DB::table('menus')->insert([
                'title'=>'مسیر مراقبت بیمار',
                'route_name'=>'portal.care-pathways.index',
                'icon'=>'IconRoute',
                'order'=>58,
                'guard'=>'doctor',
                'is_active'=>true,
                'show_in_sidebar'=>true,
                'permissions'=>json_encode(['patients.clinical.view']),
                'roles'=>json_encode(['doctor','secretary']),
                'created_at'=>now(),
                'updated_at'=>now(),
            ]);
        }
    }
    public function down(): void
    {
        if (Schema::hasTable('menus')) DB::table('menus')->where('route_name','portal.care-pathways.index')->delete();
    }
};
