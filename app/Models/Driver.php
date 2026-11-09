<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Driver extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone_number',
        'whatsapp_number',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function safariBookings(): HasMany
    {
        return $this->hasMany(SafariBooking::class);
    }

    public function transportBookings(): HasMany
    {
        return $this->hasMany(TransportBooking::class);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'default_driver_id');
    }
}
