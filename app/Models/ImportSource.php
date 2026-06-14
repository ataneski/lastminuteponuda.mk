<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A URL on an external agency website that we periodically fetch and
 * AI-extract listings from. Each new listing is created as a draft so
 * the agency reviews before publish.
 */
class ImportSource extends Model
{
    public const SCHEDULE_MANUAL = 'manual';
    public const SCHEDULE_HOURLY = 'hourly';
    public const SCHEDULE_6H     = '6h';
    public const SCHEDULE_DAILY  = 'daily';

    public const SCHEDULES = [
        self::SCHEDULE_MANUAL => 'Само рачно',
        self::SCHEDULE_HOURLY => 'На секој час',
        self::SCHEDULE_6H     => 'На секои 6 часа',
        self::SCHEDULE_DAILY  => 'Еднаш дневно',
    ];

    public const STATUS_SUCCESS = 'success';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_FAILED  = 'failed';

    public const MAX_CONSECUTIVE_FAILURES = 3;

    protected $fillable = [
        'user_id', 'label', 'url', 'schedule', 'active',
        'last_synced_at', 'last_status', 'last_error', 'last_extracted_count',
        'consecutive_failures',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'last_synced_at' => 'datetime',
            'last_extracted_count' => 'integer',
            'consecutive_failures' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Whether the scheduled tick should fire a sync for this source
     * now, given its cadence + last_synced_at.
     */
    public function isDue(): bool
    {
        if (! $this->active || $this->schedule === self::SCHEDULE_MANUAL) {
            return false;
        }
        if ($this->last_synced_at === null) {
            return true;
        }
        return match ($this->schedule) {
            self::SCHEDULE_HOURLY => $this->last_synced_at->lt(now()->subHour()),
            self::SCHEDULE_6H     => $this->last_synced_at->lt(now()->subHours(6)),
            self::SCHEDULE_DAILY  => $this->last_synced_at->lt(now()->subDay()),
            default => false,
        };
    }

    public function recordSuccess(int $extracted): void
    {
        $this->forceFill([
            'last_synced_at' => now(),
            'last_status' => $extracted > 0 ? self::STATUS_SUCCESS : self::STATUS_PARTIAL,
            'last_error' => null,
            'last_extracted_count' => $extracted,
            'consecutive_failures' => 0,
        ])->save();
    }

    public function recordFailure(string $error): void
    {
        $failures = $this->consecutive_failures + 1;
        $this->forceFill([
            'last_synced_at' => now(),
            'last_status' => self::STATUS_FAILED,
            'last_error' => mb_substr($error, 0, 2000),
            'consecutive_failures' => $failures,
            // Auto-deactivate after repeated failures to stop wasting AI tokens
            'active' => $failures < self::MAX_CONSECUTIVE_FAILURES,
        ])->save();
    }
}
