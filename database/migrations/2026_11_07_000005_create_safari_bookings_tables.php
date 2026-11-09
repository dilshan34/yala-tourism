<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('safari_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no', 30)->unique();
            $table->foreignId('primary_guest_id')->constrained('guests')->cascadeOnDelete();
            $table->foreignId('property_id')->nullable()->constrained('properties')->nullOnDelete();
            $table->string('custom_property_name')->nullable();
            $table->date('safari_date')->index();
            $table->enum('safari_type', ['half_day_morning', 'full_day', 'evening'])->index();
            $table->enum('safari_category', ['private', 'shared'])->default('private')->index();
            $table->unsignedInteger('guest_count')->default(1);
            $table->time('pickup_time');
            $table->string('pickup_location');
            $table->string('national_park_block', 100)->default('Yala Block 1');
            $table->foreignId('safari_operator_id')->nullable()->constrained('safari_operators')->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->string('driver_name')->nullable();
            $table->string('driver_phone', 50)->nullable();
            $table->string('jeep_details')->nullable();
            $table->string('shared_jeep_group_id', 50)->nullable()->index();
            $table->decimal('selling_price', 12, 2)->default(0.00);
            $table->decimal('operator_cost', 12, 2)->nullable();
            $table->decimal('profit', 12, 2)->nullable();
            $table->decimal('amount_paid', 12, 2)->default(0.00);
            $table->decimal('outstanding_amount', 12, 2)->default(0.00);
            $table->enum('payment_status', ['unpaid', 'partially_paid', 'paid', 'refunded'])->default('unpaid')->index();
            $table->enum('operational_status', ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled'])->default('pending')->index();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['safari_date', 'operational_status']);
        });

        Schema::create('safari_guest_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('safari_booking_id')->constrained('safari_bookings')->cascadeOnDelete();
            $table->foreignId('guest_id')->constrained('guests')->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('safari_guest_participants');
        Schema::dropIfExists('safari_bookings');
    }
};
