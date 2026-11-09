<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'registration_number',
        'vehicle_type',
        'description',
        'default_driver_id',
        'passenger_capacity',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'passenger_capacity' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function defaultDriver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'default_driver_id');
    }

    public function transportBookings(): HasMany
    {
        return $this->hasMany(TransportBooking::class);
    }
}
