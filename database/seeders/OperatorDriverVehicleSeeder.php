<?php

namespace Database\Seeders;

use App\Models\Driver;
use App\Models\SafariOperator;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class OperatorDriverVehicleSeeder extends Seeder
{
    public function run(): void
    {
        // Safari Operators
        $op1 = SafariOperator::firstOrCreate(
            ['name' => 'Yala Wilderness Expeditions'],
            [
                'contact_person' => 'Sunil Wickramasinghe',
                'phone' => '+94 77 123 4567',
                'email' => 'sunil@yalawilderness.lk',
                'is_active' => true,
            ]
        );

        $op2 = SafariOperator::firstOrCreate(
            ['name' => 'Ruhunu Safari Jeeps'],
            [
                'contact_person' => 'Chaminda Silva',
                'phone' => '+94 71 987 6543',
                'email' => 'contact@ruhunusafari.lk',
                'is_active' => true,
            ]
        );

        // Drivers
        $driver1 = Driver::firstOrCreate(
            ['phone_number' => '+94 77 234 5678'],
            [
                'name' => 'Kamal Perera',
                'whatsapp_number' => '+94 77 234 5678',
                'notes' => 'Senior English speaking driver with 12 years experience in southern routes and national parks.',
                'is_active' => true,
            ]
        );

        $driver2 = Driver::firstOrCreate(
            ['phone_number' => '+94 76 345 6789'],
            [
                'name' => 'Nimal Silva',
                'whatsapp_number' => '+94 76 345 6789',
                'notes' => 'Specializes in airport pickups, punctual and knowledgeable.',
                'is_active' => true,
            ]
        );

        $driver3 = Driver::firstOrCreate(
            ['phone_number' => '+94 70 456 7890'],
            [
                'name' => 'Priyantha Bandara',
                'whatsapp_number' => '+94 70 456 7890',
                'notes' => 'Experienced in 4x4 rugged routes and long-distance intercity transfers.',
                'is_active' => true,
            ]
        );

        // Vehicles
        Vehicle::firstOrCreate(
            ['registration_number' => 'WP-CAB-4521'],
            [
                'vehicle_type' => 'Van (Toyota KDH Luxury)',
                'description' => 'Dual AC, high roof, reclining seats, luggage rack, WiFi onboard.',
                'default_driver_id' => $driver1->id,
                'passenger_capacity' => 8,
                'is_active' => true,
            ]
        );

        Vehicle::firstOrCreate(
            ['registration_number' => 'SP-KX-8910'],
            [
                'vehicle_type' => 'Sedan (Toyota Prius Hybrid)',
                'description' => 'Comfortable 3-seater sedan, climate control, smooth ride.',
                'default_driver_id' => $driver2->id,
                'passenger_capacity' => 3,
                'is_active' => true,
            ]
        );

        Vehicle::firstOrCreate(
            ['registration_number' => 'EP-LK-1122'],
            [
                'vehicle_type' => 'SUV (Toyota Land Cruiser Prado)',
                'description' => 'Luxury 4x4 SUV suitable for upcountry and remote wilderness excursions.',
                'default_driver_id' => $driver3->id,
                'passenger_capacity' => 4,
                'is_active' => true,
            ]
        );
    }
}
