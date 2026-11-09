<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SafariBooking extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reference_no',
        'primary_guest_id',
        'property_id',
        'custom_property_name',
        'safari_date',
        'safari_type',
        'safari_category',
        'guest_count',
        'pickup_time',
        'pickup_location',
        'national_park_block',
        'safari_operator_id',
        'driver_id',
        'driver_name',
        'driver_phone',
        'jeep_details',
        'shared_jeep_group_id',
        'selling_price',
        'operator_cost',
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
            'safari_date' => 'date',
            'guest_count' => 'integer',
            'selling_price' => 'decimal:2',
            'operator_cost' => 'decimal:2',
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

        static::saving(function (SafariBooking $booking) {
            // Profit calculation: only compute if operator cost is recorded
            if ($booking->operator_cost !== null && $booking->operator_cost !== '') {
                $booking->profit = (float) $booking->selling_price - (float) $booking->operator_cost;
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

    public function operator(): BelongsTo
    {
        return $this->belongsTo(SafariOperator::class, 'safari_operator_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by_user_id');
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(Guest::class, 'safari_guest_participants')
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
        return $query->whereDate('safari_date', today());
    }

    public function scopeTomorrow($query)
    {
        return $query->whereDate('safari_date', today()->addDay());
    }

    public function scopePending($query)
    {
        return $query->whereIn('operational_status', ['pending', 'confirmed', 'in_progress']);
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
