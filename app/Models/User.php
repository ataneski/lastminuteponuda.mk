<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

#[Fillable([
    'name', 'email', 'password',
    'slug', 'display_name', 'tagline', 'description',
    'logo_url', 'cover_url', 'website', 'phone',
    'address', 'accent_color',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const RESERVED_SLUGS = [
        'nov-oglas', 'moi-oglasi', 'profil', 'oglas', 'login',
        'register', 'logout', 'admin', 'api', 'sitemap', 'robots',
    ];

    public const DEFAULT_ACCENT = '#0284c7';

    protected static function booted(): void
    {
        static::creating(function (self $user) {
            $user->slug ??= self::generateUniqueSlug($user->name ?? 'agencija');
        });
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public static function generateUniqueSlug(string $source, ?int $ignoreId = null): string
    {
        $base = Str::slug($source);
        if ($base === '' || in_array($base, self::RESERVED_SLUGS, true)) {
            $base = 'agencija';
        }

        $slug = $base;
        $i = 2;
        while (
            self::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }

    public function getBrandNameAttribute(): string
    {
        return $this->display_name ?: $this->name;
    }

    public function getAccentAttribute(): string
    {
        return $this->accent_color ?: self::DEFAULT_ACCENT;
    }
}
