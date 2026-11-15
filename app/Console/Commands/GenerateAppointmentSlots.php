<?php
// app/Console/Commands/GenerateAppointmentSlots.php
namespace App\Console\Commands;

use App\Services\AppointmentSlotGenerator;
use Illuminate\Console\Command;

class GenerateAppointmentSlots extends Command
{
    protected $signature = 'appointments:generate {--days=7 : تعداد روزهای آینده}';
    protected $description = 'تولید نوبت‌های قابل رزرو بر اساس ساعات کاری پزشکان';

    public function handle(AppointmentSlotGenerator $generator): int
    {
        $days = (int) $this->option('days');
        
        $this->info("🔄 در حال تولید نوبت‌ها برای {$days} روز آینده...");
        
        $generator->generateForAllDoctors($days);
        
        $this->info('✅ نوبت‌ها با موفقیت تولید شدند.');
        
        return Command::SUCCESS;
    }
}