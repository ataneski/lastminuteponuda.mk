<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily 03:00 — clean up drafts that the agency never finished
// (created via AI wizard / WhatsApp but never published).
Schedule::command('listings:purge-stale-drafts')->dailyAt('03:00');

// Hourly — dispatch syncs for any due ImportSource. The job itself
// is unique-keyed on source_id so overlapping ticks don't double-fire.
Schedule::command('import:sync-due')->hourly();
