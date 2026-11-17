<?php

namespace App\Console\Commands;

use App\Actions\Portal\Shifts\GenerateSlotsForAllDoctorsAction;
use Illuminate\Console\Command;

class GenerateAppointmentSlots extends Command
{
    protected $signature = 'appointments:generate
        {--weeks=1 : تعداد هفته‌های آینده}
        {--days= : گزینه قدیمی؛ تعداد روزها به هفته کامل تبدیل می‌شود}';

    protected $description = 'تولید نوبت‌های قابل رزرو بر اساس ساعات کاری فعال پزشکان';

    public function handle(GenerateSlotsForAllDoctorsAction $action): int
    {
        $weeks = (int) $this->option('weeks');
        $days = $this->option('days');

        if ($days !== null) {
            $days = (int) $days;

            if ($days < 1 || $days > 364) {
                $this->error('تعداد روز باید بین ۱ تا ۳۶۴ باشد.');

                return Command::FAILURE;
            }

            $weeks = (int) ceil($days / 7);
        }

        if ($weeks < 1 || $weeks > 52) {
            $this->error('تعداد هفته باید بین ۱ تا ۵۲ باشد.');

            return Command::FAILURE;
        }

        $this->info("در حال تولید نوبت‌ها برای {$weeks} هفته آینده...");

        $result = $action->handle($weeks);

        $this->info("پزشکان فعال بررسی‌شده: {$result['doctors']}");
        $this->info("نوبت‌های ایجادشده: {$result['generated']}");
        $this->info("بازه‌های تکراری یا متداخل: {$result['skipped']}");
        $this->info("پزشکان بدون الگوی کاری فعال: {$result['without_working_hours']}");

        return Command::SUCCESS;
    }
}
