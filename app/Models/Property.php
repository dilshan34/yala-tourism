<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Property extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'code',
        'description',
        'address',
        'is_predefined',
        'is_active',
        'color_hex',
    ];

    protected function casts(): array
    {
        return [
            'is_predefined' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function activeRooms(): HasMany
    {
        return $this->hasMany(Room::class)->where('is_active', true);
    }

    public function calendarConnections(): HasMany
    {
        return $this->hasMany(CalendarConnection::class);
    }

    public function calendarEvents(): HasMany
    {
        return $this->hasMany(CalendarEvent::class);
    }

    public function safariBookings(): HasMany
    {
        return $this->hasMany(SafariBooking::class);
    }

    public function transportBookings(): HasMany
    {
        return $this->hasMany(TransportBooking::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
