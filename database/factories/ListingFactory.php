<?php

namespace Database\Factories;

use App\Models\Listing;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Listing>
 */
class ListingFactory extends Factory
{
    public function definition(): array
    {
        $departure = $this->faker->dateTimeBetween('+1 week', '+2 months');
        $nights = $this->faker->numberBetween(3, 10);
        $return = (clone $departure)->modify("+{$nights} days");

        return [
            'agency_name' => $this->faker->company(),
            'agency_contact' => $this->faker->phoneNumber(),
            'title' => $nights.' ноќи '.$this->faker->city(),
            'destination' => $this->faker->city(),
            'country' => $this->faker->country(),
            'hotel_name' => 'Hotel '.$this->faker->lastName(),
            'hotel_stars' => $this->faker->numberBetween(3, 5),
            'board_type' => $this->faker->randomElement(array_keys(Listing::BOARD_TYPES)),
            'transport' => $this->faker->randomElement(array_keys(Listing::TRANSPORTS)),
            'departure_date' => $departure->format('Y-m-d'),
            'return_date' => $return->format('Y-m-d'),
            'nights' => $nights,
            'price_per_person' => $this->faker->numberBetween(199, 1499),
            'currency' => 'EUR',
            'available_seats' => $this->faker->numberBetween(2, 10),
            'description' => $this->faker->paragraph(4),
            'features' => $this->faker->randomElements(
                ['Базен', 'Wi-Fi', 'Анимација за деца', 'Спа центар', 'Плажа на 5 мин', 'Паркинг'],
                $this->faker->numberBetween(2, 5)
            ),
            'image_url' => null,
        ];
    }
}
