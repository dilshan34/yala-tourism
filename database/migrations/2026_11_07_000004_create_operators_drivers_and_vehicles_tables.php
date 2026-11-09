<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('safari_operators', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact_person')->nullable();
            $table->string('phone', 50);
            $table->string('email')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->string('phone_number', 50);
            $table->string('whatsapp_number', 50)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('registration_number', 50)->unique();
            $table->string('vehicle_type', 100); // e.g. Van (KDH), Sedan (Prius), SUV, Mini Bus
            $table->text('description')->nullable();
            $table->foreignId('default_driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->unsignedInteger('passenger_capacity')->default(4);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('drivers');
        Schema::dropIfExists('safari_operators');
    }
};
