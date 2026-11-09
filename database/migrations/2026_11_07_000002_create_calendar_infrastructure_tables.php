<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->enum('platform', ['airbnb', 'booking_com', 'other'])->default('other');
            $table->string('calendar_name');
            $table->text('ical_url'); // Encrypted at rest via Eloquent attribute casting
            $table->enum('status', ['active', 'paused', 'failed'])->default('active')->index();
            $table->unsignedInteger('sync_interval_minutes')->default(15);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamp('last_successful_sync_at')->nullable();
            $table->text('last_error_message')->nullable();
            $table->unsignedInteger('last_http_code')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['room_id', 'platform']);
        });

        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calendar_connection_id')->constrained('calendar_connections')->cascadeOnDelete();
            $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->enum('source_platform', ['airbnb', 'booking_com', 'other']);
            $table->string('source_event_uid', 255);
            $table->string('summary', 255)->nullable();
            $table->date('check_in_date')->index();
            $table->date('check_out_date')->index();
            $table->unsignedInteger('booked_nights'); // Check-out date is exclusive
            $table->enum('status', ['confirmed', 'blocked', 'unknown', 'cancelled'])->default('confirmed')->index();
            $table->boolean('is_duplicate_suspect')->default(false)->index();
            $table->boolean('is_confirmed_duplicate')->default(false)->index();
            $table->unsignedBigInteger('duplicate_master_event_id')->nullable()->index();
            $table->boolean('conflict_detected')->default(false)->index();
            $table->json('raw_metadata')->nullable();
            $table->timestamp('last_seen_in_feed_at');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['calendar_connection_id', 'source_event_uid'], 'uniq_cal_conn_event_uid');
            $table->index(['room_id', 'check_in_date', 'check_out_date'], 'idx_room_dates');
        });

        Schema::create('calendar_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calendar_connection_id')->constrained('calendar_connections')->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->enum('status', ['success', 'failed', 'timeout'])->index();
            $table->unsignedInteger('events_found')->default(0);
            $table->unsignedInteger('events_created')->default(0);
            $table->unsignedInteger('events_updated')->default(0);
            $table->unsignedInteger('events_removed')->default(0);
            $table->unsignedInteger('http_response_time_ms')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('calendar_event_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->foreignId('primary_event_id')->constrained('calendar_events')->cascadeOnDelete();
            $table->foreignId('matched_event_id')->constrained('calendar_events')->cascadeOnDelete();
            $table->enum('match_type', ['potential_overlap', 'user_confirmed_duplicate', 'user_marked_distinct_conflict'])->index();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_event_matches');
        Schema::dropIfExists('calendar_sync_logs');
        Schema::dropIfExists('calendar_events');
        Schema::dropIfExists('calendar_connections');
    }
};
