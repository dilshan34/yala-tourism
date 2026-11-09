<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransportBooking extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reference_no',
        'primary_guest_id',
        'property_id',
        'custom_property_name',
        'transport_date',
        'pickup_time',
        'pickup_location',
        'drop_off_location',
        'additional_stops',
        'transport_type',
        'driver_id',
        'vehicle_id',
        'driver_name',
        'driver_phone',
        'selling_price',
        'driver_cost',
        'profit',
        'amount_paid',
        'outstanding_amount',
        'payment_status',
        'operational_status',
        'completed_at',
        'completed_by_user_id',
        'reminder_sent_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'transport_date' => 'date',
            'selling_price' => 'decimal:2',
            'driver_cost' => 'decimal:2',
            'profit' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'outstanding_amount' => 'decimal:2',
            'completed_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function (TransportBooking $booking) {
            // Profit calculation
            if ($booking->driver_cost !== null && $booking->driver_cost !== '') {
                $booking->profit = (float) $booking->selling_price - (float) $booking->driver_cost;
            } else {
                $booking->profit = null;
            }

            // Outstanding balance calculation
            $booking->outstanding_amount = max(0, (float) $booking->selling_price - (float) $booking->amount_paid);

            // Update payment status
            if ((float) $booking->amount_paid <= 0) {
                $booking->payment_status = 'unpaid';
            } elseif ((float) $booking->amount_paid >= (float) $booking->selling_price) {
                $booking->payment_status = 'paid';
            } else {
                $booking->payment_status = 'partially_paid';
            }
        });
    }

    public function primaryGuest(): BelongsTo
    {
        return $this->belongsTo(Guest::class, 'primary_guest_id');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by_user_id');
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(Guest::class, 'transport_guest_participants')
                    ->withPivot('is_primary')
                    ->withTimestamps();
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    public function getEffectivePropertyNameAttribute(): string
    {
        if ($this->property_id && $this->property) {
            return $this->property->name;
        }
        if (!empty($this->custom_property_name)) {
            return $this->custom_property_name;
        }
        return 'External / No Accommodation';
    }

    public function scopeToday($query)
    {
        return $query->whereDate('transport_date', today());
    }

    public function scopeTomorrow($query)
    {
        return $query->whereDate('transport_date', today()->addDay());
    }

    public function scopePending($query)
    {
        return $query->whereIn('operational_status', ['pending', 'confirmed', 'driver_assigned', 'in_progress']);
    }

    public function scopeCompleted($query)
    {
        return $query->where('operational_status', 'completed');
    }

    public function syncPaymentsLedger(): void
    {
        $totalPaid = $this->payments()->sum('amount');
        $this->amount_paid = $totalPaid;
        $this->save();
    }
}
