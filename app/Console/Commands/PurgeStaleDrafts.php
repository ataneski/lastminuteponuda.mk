<?php

namespace App\Console\Commands;

use App\Models\Listing;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Deletes listings that were created via the AI wizard or WhatsApp
 * flow but never published. Default cutoff is 7 days, override with
 * --days. Cascade deletes attached ListingImage rows.
 *
 * Scheduled daily via routes/console.php.
 */
#[Signature('listings:purge-stale-drafts {--days=7 : Drafts older than this many days are deleted}')]
#[Description('Purge unpublished drafts older than the configured age.')]
class PurgeStaleDrafts extends Command
{
    public function handle(): int
    {
        $days = (int) $this->option('days');
        if ($days < 1) {
            $this->error('--days must be at least 1.');

            return self::INVALID;
        }

        $cutoff = now()->subDays($days);
        $count = Listing::draft()
            ->where('created_at', '<', $cutoff)
            ->count();

        if ($count === 0) {
            $this->info('No stale drafts to purge.');

            return self::SUCCESS;
        }

        Listing::draft()
            ->where('created_at', '<', $cutoff)
            ->each(fn (Listing $l) => $l->delete());

        $this->info("Purged {$count} draft listings older than {$days} day(s).");

        return self::SUCCESS;
    }
}
