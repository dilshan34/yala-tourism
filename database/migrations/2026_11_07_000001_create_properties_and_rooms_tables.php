<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('code', 20)->nullable()->unique();
            $table->text('description')->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_predefined')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->string('color_hex', 10)->default('#2563EB');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
            $table->string('name');
            $table->string('room_reference', 50)->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('max_occupancy')->default(2);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['property_id', 'name']);
        });

        Schema::create('room_unavailabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date'); // Exclusive check-out convention
            $table->string('reason');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['room_id', 'start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_unavailabilities');
        Schema::dropIfExists('rooms');
        Schema::dropIfExists('properties');
    }
};
