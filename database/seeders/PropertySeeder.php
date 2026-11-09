<?php

namespace Database\Seeders;

use App\Models\Property;
use App\Models\Room;
use Illuminate\Database\Seeder;

class PropertySeeder extends Seeder
{
    public function run(): void
    {
        // Wild Pulse Property
        $wildPulse = Property::firstOrCreate(
            ['slug' => 'wild-pulse'],
            [
                'name' => 'Wild Pulse',
                'code' => 'WP',
                'description' => 'Luxury eco-lodge bordering Yala National Park buffer zone with serene jungle views.',
                'address' => 'Palatupana, Yala, Kirinda, Sri Lanka',
                'is_predefined' => true,
                'is_active' => true,
                'color_hex' => '#2563EB', // Blue
            ]
        );

        $wildPulseRooms = [
            ['name' => 'Room 1', 'room_reference' => 'WP-R1', 'description' => 'Deluxe King Jungle View Chalet', 'max_occupancy' => 2],
            ['name' => 'Room 2', 'room_reference' => 'WP-R2', 'description' => 'Superior Double Safari Tent', 'max_occupancy' => 2],
            ['name' => 'Room 3', 'room_reference' => 'WP-R3', 'description' => 'Family Suite with Private Plunge Pool', 'max_occupancy' => 4],
        ];

        foreach ($wildPulseRooms as $roomData) {
            Room::firstOrCreate(
                ['property_id' => $wildPulse->id, 'name' => $roomData['name']],
                $roomData
            );
        }

        // Yala Bliss Property
        $yalaBliss = Property::firstOrCreate(
            ['slug' => 'yala-bliss'],
            [
                'name' => 'Yala Bliss',
                'code' => 'YB',
                'description' => 'Charming boutique villa nestled among ancient tamarind trees close to Katagamuwa entrance.',
                'address' => 'Katagamuwa Road, Kataragama, Sri Lanka',
                'is_predefined' => true,
                'is_active' => true,
                'color_hex' => '#9333EA', // Purple
            ]
        );

        $yalaBlissRooms = [
            ['name' => 'Room 1', 'room_reference' => 'YB-R1', 'description' => 'Tamarind Tree Garden Suite', 'max_occupancy' => 2],
            ['name' => 'Room 2', 'room_reference' => 'YB-R2', 'description' => 'Lakeview Verandah Cottage', 'max_occupancy' => 3],
        ];

        foreach ($yalaBlissRooms as $roomData) {
            Room::firstOrCreate(
                ['property_id' => $yalaBliss->id, 'name' => $roomData['name']],
                $roomData
            );
        }
    }
}
