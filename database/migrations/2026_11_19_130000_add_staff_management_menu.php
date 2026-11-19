<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('menus')) return;
        DB::table('menus')->updateOrInsert(
            ['route_name'=>'portal.staff-management.index'],
            [
                'title'=>'مدیریت منابع انسانی',
                'icon'=>'IconUsersRound',
                'order'=>61,
                'guard'=>'doctor',
                'is_active'=>true,
                'show_in_sidebar'=>true,
                'permissions'=>json_encode(['staff.attendance.view']),
                'roles'=>json_encode(['doctor']),
                'created_at'=>now(),
                'updated_at'=>now(),
            ]
        );
    }

    public function down(): void
    {
        if (Schema::hasTable('menus')) {
            DB::table('menus')->where('route_name','portal.staff-management.index')->delete();
        }
    }
};
