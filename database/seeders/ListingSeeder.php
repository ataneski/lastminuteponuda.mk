<?php

namespace Database\Seeders;

use App\Models\Listing;
use Illuminate\Database\Seeder;

class ListingSeeder extends Seeder
{
    public function run(): void
    {
        Listing::create([
            'agency_name' => 'Балкан Травел',
            'agency_contact' => '+389 70 123 456',
            'title' => '7 ноќи Анталија — All Inclusive со авион',
            'destination' => 'Анталија',
            'country' => 'Турција',
            'hotel_name' => 'Royal Seginus',
            'hotel_stars' => 5,
            'board_type' => 'ultraAllInclusive',
            'transport' => 'plane',
            'departure_date' => '2026-06-12',
            'return_date' => '2026-06-19',
            'nights' => 7,
            'price_per_person' => 599,
            'currency' => 'EUR',
            'available_seats' => 4,
            'description' => 'Last minute понуда за лето во Анталија. Луксузен 5* хотел со ultra all inclusive услуга, директно на плажа.',
            'features' => ['Базен', 'Плажа на 5 мин', 'Wi-Fi', 'Анимација за деца', 'Спа центар'],
            'image_url' => 'https://images.unsplash.com/photo-1582719508461-905c673771fd?w=1200',
            'published_at' => now(),
        ]);

        Listing::create([
            'agency_name' => 'Sun Travel Skopje',
            'agency_contact' => 'info@suntravel.mk',
            'title' => '5 ноќи Халкидики — полупансион со автобус',
            'destination' => 'Касандра',
            'country' => 'Грција',
            'hotel_name' => 'Possidi Holidays',
            'hotel_stars' => 4,
            'board_type' => 'halfBoard',
            'transport' => 'bus',
            'departure_date' => '2026-06-20',
            'return_date' => '2026-06-25',
            'nights' => 5,
            'price_per_person' => 349,
            'currency' => 'EUR',
            'available_seats' => 6,
            'description' => 'Краток одмор на Касандра. Хотел во Посиди со полупансион, директен автобуски превоз од Скопје.',
            'features' => ['Базен', 'Плажа на 5 мин', 'Wi-Fi', 'Паркинг'],
            'image_url' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=1200',
        ]);
    }
}
