<?php
declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up():void {
        if(!Schema::hasTable('menus')) return;
        if(!DB::table('menus')->where('route_name','portal.shifts.capacity')->exists()){
            DB::table('menus')->insert([
                'title'=>'تحلیل ظرفیت مطب','route_name'=>'portal.shifts.capacity','icon'=>'IconChartBar',
                'order'=>59,'guard'=>'doctor','is_active'=>true,'show_in_sidebar'=>true,
                'permissions'=>json_encode(['shifts.view']),'roles'=>json_encode(['doctor','admin','secretary']),
                'created_at'=>now(),'updated_at'=>now(),
            ]);
        }
    }
    public function down():void {
        if(Schema::hasTable('menus')) DB::table('menus')->where('route_name','portal.shifts.capacity')->delete();
    }
};
