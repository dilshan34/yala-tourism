<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guests', function (Blueprint $table) {
            $table->id();
            $table->string('full_name')->index();
            $table->string('phone_number', 50)->index();
            $table->string('whatsapp_number', 50)->nullable();
            $table->string('country', 100)->index();
            $table->string('email', 191)->nullable()->index();
            $table->string('passport_number', 50)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['phone_number', 'full_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guests');
    }
};
