<?php

namespace Database\Seeders;

use App\Models\Guest;
use Illuminate\Database\Seeder;

class GuestSeeder extends Seeder
{
    public function run(): void
    {
        $guests = [
            [
                'full_name' => 'Alexander von Schmidt',
                'phone_number' => '+49 170 1234567',
                'whatsapp_number' => '+49 170 1234567',
                'country' => 'Germany',
                'email' => 'alex.schmidt@berlin-travel.de',
                'passport_number' => 'C01X77992',
                'notes' => 'Wildlife photographer interested in leopards and birds.',
            ],
            [
                'full_name' => 'Sophie Laurent',
                'phone_number' => '+33 6 12 34 56 78',
                'whatsapp_number' => '+33 6 12 34 56 78',
                'country' => 'France',
                'email' => 'sophie.laurent@paris.fr',
                'passport_number' => '19AB9921',
                'notes' => 'Vegetarian, requested morning safari with experienced naturalist.',
            ],
            [
                'full_name' => 'David Miller',
                'phone_number' => '+44 7911 123456',
                'whatsapp_number' => '+44 7911 123456',
                'country' => 'United Kingdom',
                'email' => 'd.miller@london.co.uk',
                'passport_number' => 'GB9823411',
                'notes' => 'Traveling with family (2 adults, 2 children).',
            ],
            [
                'full_name' => 'Elena Rostova',
                'phone_number' => '+7 903 123-45-67',
                'whatsapp_number' => '+7 903 123-45-67',
                'country' => 'Russia',
                'email' => 'elena.rostova@yandex.ru',
                'passport_number' => '75N883921',
                'notes' => 'Airport pickup and full day safari in Yala Block 1.',
            ],
            [
                'full_name' => 'Liam O\'Connor',
                'phone_number' => '+61 412 345 678',
                'whatsapp_number' => '+61 412 345 678',
                'country' => 'Australia',
                'email' => 'liam.oc@melbourne.au',
                'passport_number' => 'PA908821',
                'notes' => 'Surfer visiting Arugam Bay and Yala.',
            ],
        ];

        foreach ($guests as $guestData) {
            Guest::firstOrCreate(
                ['phone_number' => $guestData['phone_number']],
                $guestData
            );
        }
    }
}
