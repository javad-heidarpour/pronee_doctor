<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Portal\Patient\BackfillPatientsFromAppointmentsAction;
use Illuminate\Console\Command;

final class BackfillPatientsFromAppointments extends Command
{
    protected $signature = 'patients:backfill-appointments {--chunk=200 : Number of appointments processed per batch}';

    protected $description = 'Create doctor-scoped patient records for legacy appointments without changing visit data.';

    public function handle(BackfillPatientsFromAppointmentsAction $action): int
    {
        $chunkSize = (int) $this->option('chunk');
        $stats = $action->handle($chunkSize);

        $this->info('Patient backfill completed.');
        $this->table(
            ['Processed', 'Linked', 'Skipped'],
            [[$stats['processed'], $stats['linked'], $stats['skipped']]]
        );

        return self::SUCCESS;
    }
}
