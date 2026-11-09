<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payable_type', 100);
            $table->unsignedBigInteger('payable_id');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('LKR');
            $table->date('payment_date')->index();
            $table->enum('payment_method', ['cash', 'bank_transfer', 'card', 'other'])->default('cash');
            $table->string('transaction_reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['payable_type', 'payable_id']);
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject_type', 100);
            $table->unsignedBigInteger('subject_id');
            $table->string('action', 50); // e.g. status_changed, payment_recorded, sync_completed
            $table->text('description');
            $table->json('changes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('payments');
    }
};
