<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transport_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no', 30)->unique();
            $table->foreignId('primary_guest_id')->constrained('guests')->cascadeOnDelete();
            $table->foreignId('property_id')->nullable()->constrained('properties')->nullOnDelete();
            $table->string('custom_property_name')->nullable();
            $table->date('transport_date')->index();
            $table->time('pickup_time');
            $table->string('pickup_location');
            $table->string('drop_off_location');
            $table->text('additional_stops')->nullable();
            $table->enum('transport_type', [
                'airport_pickup',
                'airport_drop',
                'hotel_transfer',
                'intercity',
                'day_tour',
                'other'
            ])->index();
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->string('driver_name')->nullable();
            $table->string('driver_phone', 50)->nullable();
            $table->decimal('selling_price', 12, 2)->default(0.00);
            $table->decimal('driver_cost', 12, 2)->nullable();
            $table->decimal('profit', 12, 2)->nullable();
            $table->decimal('amount_paid', 12, 2)->default(0.00);
            $table->decimal('outstanding_amount', 12, 2)->default(0.00);
            $table->enum('payment_status', ['unpaid', 'partially_paid', 'paid', 'refunded'])->default('unpaid')->index();
            $table->enum('operational_status', ['pending', 'confirmed', 'driver_assigned', 'in_progress', 'completed', 'cancelled'])->default('pending')->index();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['transport_date', 'operational_status']);
        });

        Schema::create('transport_guest_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transport_booking_id')->constrained('transport_bookings')->cascadeOnDelete();
            $table->foreignId('guest_id')->constrained('guests')->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_guest_participants');
        Schema::dropIfExists('transport_bookings');
    }
};
