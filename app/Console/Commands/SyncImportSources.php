<?php

namespace App\Console\Commands;

use App\Jobs\SyncImportSourceJob;
use App\Models\ImportSource;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Hourly tick — dispatches sync jobs for every ImportSource whose
 * cadence says it's due. Scheduled in routes/console.php.
 */
#[Signature('import:sync-due')]
#[Description('Dispatch SyncImportSourceJob for every ImportSource that is due.')]
class SyncImportSources extends Command
{
    public function handle(): int
    {
        $sources = ImportSource::where('active', true)
            ->where('schedule', '!=', ImportSource::SCHEDULE_MANUAL)
            ->get();

        $dispatched = 0;
        foreach ($sources as $source) {
            if ($source->isDue()) {
                SyncImportSourceJob::dispatch($source->id);
                $dispatched++;
            }
        }

        $this->info("Dispatched {$dispatched} sync job(s).");

        return self::SUCCESS;
    }
}
