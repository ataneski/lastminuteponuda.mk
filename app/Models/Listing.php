<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
            'features' => 'array',
            'hotel_stars' => 'integer',
            'nights' => 'integer',
            'price_per_person' => 'integer',
            'available_seats' => 'integer',
        ];
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
