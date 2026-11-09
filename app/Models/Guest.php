<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Guest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'full_name',
        'phone_number',
        'whatsapp_number',
        'country',
        'email',
        'passport_number',
        'notes',
    ];

    public function safariBookings(): HasMany
    {
        return $this->hasMany(SafariBooking::class, 'primary_guest_id');
    }

    public function participatedSafaris(): BelongsToMany
    {
        return $this->belongsToMany(SafariBooking::class, 'safari_guest_participants')
                    ->withPivot('is_primary')
                    ->withTimestamps();
    }

    public function transportBookings(): HasMany
    {
        return $this->hasMany(TransportBooking::class, 'primary_guest_id');
    }

    public function participatedTransports(): BelongsToMany
    {
        return $this->belongsToMany(TransportBooking::class, 'transport_guest_participants')
                    ->withPivot('is_primary')
                    ->withTimestamps();
    }
}
