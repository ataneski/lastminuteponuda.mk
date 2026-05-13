<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Listing extends Model
{
    /** @use HasFactory<\Database\Factories\ListingFactory> */
    use HasFactory;

    public const BOARD_TYPES = [
        'noBoard' => 'Без оброци',
        'breakfast' => 'Појадок',
        'halfBoard' => 'Полупансион',
        'fullBoard' => 'Полн пансион',
        'allInclusive' => 'All inclusive',
        'ultraAllInclusive' => 'Ultra all inclusive',
    ];

    public const TRANSPORTS = [
        'bus' => 'Автобус',
        'plane' => 'Авион',
        'ownTransport' => 'Сопствен превоз',
        'ferry' => 'Траект',
    ];

    public const CURRENCIES = ['EUR', 'MKD', 'USD'];

    protected $fillable = [
        'user_id',
        'expires_at',
        'featured_until',
        'published_at',
        'ai_generated_at',
        'ai_raw_response',
        'suspended_at',
        'suspension_reason',
        'agency_name',
        'agency_contact',
        'title',
        'destination',
        'country',
        'hotel_name',
        'hotel_stars',
        'board_type',
        'transport',
        'departure_date',
        'return_date',
        'nights',
        'price_per_person',
        'currency',
        'available_seats',
        'description',
        'features',
        'image_url',
    ];

    protected function casts(): array
    {
        return [
            'departure_date' => 'date',
            'return_date' => 'date',
            'expires_at' => 'datetime',
            'featured_until' => 'datetime',
            'published_at' => 'datetime',
            'ai_generated_at' => 'datetime',
            'suspended_at' => 'datetime',
            'ai_raw_response' => 'array',
            'features' => 'array',
            'hotel_stars' => 'integer',
            'nights' => 'integer',
            'price_per_person' => 'integer',
            'available_seats' => 'integer',
            'views_count' => 'integer',
        ];
    }

    /**
     * Active = published AND not expired AND not suspended. Drafts
     * (published_at IS NULL) are excluded from public views.
     */
    public function scopeActive($query)
    {
        return $query
            ->whereNotNull('published_at')
            ->whereNull('suspended_at')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    public function suspend(?string $reason = null): void
    {
        $this->forceFill([
            'suspended_at' => now(),
            'suspension_reason' => $reason,
        ])->save();
    }

    public function unsuspend(): void
    {
        $this->forceFill([
            'suspended_at' => null,
            'suspension_reason' => null,
        ])->save();
    }

    public function scopeDraft($query)
    {
        return $query->whereNull('published_at');
    }

    public function isDraft(): bool
    {
        return $this->published_at === null;
    }

    public function publish(): self
    {
        if ($this->published_at === null) {
            $this->forceFill(['published_at' => now()])->save();
        }

        return $this;
    }

    public function scopeFeatured($query)
    {
        return $query->whereNotNull('featured_until')->where('featured_until', '>', now());
    }

    public function isFeatured(): bool
    {
        return $this->featured_until !== null && $this->featured_until->isFuture();
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')->where('expires_at', '<=', now());
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ListingImage::class)->orderBy('position');
    }

    public function inquiries(): HasMany
    {
        return $this->hasMany(Inquiry::class);
    }

    public function dailyViews(): HasMany
    {
        return $this->hasMany(ListingView::class)->orderBy('day');
    }

    public function getPrimaryImageUrlAttribute(): ?string
    {
        return $this->images->first()?->url ?? $this->image_url;
    }

    public function getBoardLabelAttribute(): string
    {
        return self::BOARD_TYPES[$this->board_type] ?? $this->board_type;
    }

    public function getTransportLabelAttribute(): string
    {
        return self::TRANSPORTS[$this->transport] ?? $this->transport;
    }

    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price_per_person, 0, ',', '.').' '.$this->currency;
    }
}
