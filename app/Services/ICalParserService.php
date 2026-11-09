<?php

namespace App\Services;

use App\Models\CalendarConnection;
use App\Models\CalendarEvent;
use App\Models\CalendarSyncLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Sabre\VObject\Reader;

class ICalParserService
{
    protected CalendarDeduplicationService $deduplicationService;

    public function __construct(CalendarDeduplicationService $deduplicationService)
    {
        $this->deduplicationService = $deduplicationService;
    }

    /**
     * Test connection to a calendar URL without saving events.
     * Returns array with 'success' boolean, 'http_code', 'events_found', 'error'.
     */
    public function testConnection(string $url): array
    {
        try {
            $startTime = microtime(true);
            $response = Http::timeout(15)
                ->retry(2, 500)
                ->withHeaders([
                    'User-Agent' => 'TourismBusinessManager/1.0 (Mozilla/5.0 compatible)',
                    'Accept' => 'text/calendar, text/plain, */*',
                ])
                ->get($url);

            $durationMs = (int) round((microtime(true) - $startTime) * 1000);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'http_code' => $response->status(),
                    'events_found' => 0,
                    'duration_ms' => $durationMs,
                    'error' => "HTTP server returned error code {$response->status()}",
                ];
            }

            $body = $response->body();
            if (empty(trim($body))) {
                return [
                    'success' => false,
                    'http_code' => $response->status(),
                    'events_found' => 0,
                    'duration_ms' => $durationMs,
                    'error' => 'The calendar feed returned an empty response.',
                ];
            }

            $vcalendar = Reader::read($body);
            $eventsCount = count($vcalendar->VEVENT ?? []);

            return [
                'success' => true,
                'http_code' => $response->status(),
                'events_found' => $eventsCount,
                'duration_ms' => $durationMs,
                'error' => null,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'http_code' => null,
                'events_found' => 0,
                'duration_ms' => null,
                'error' => 'Connection failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Synchronize events for a given calendar connection.
     */
    public function syncConnection(CalendarConnection $connection): array
    {
        $startedAt = now();
        $startTime = microtime(true);

        $eventsFound = 0;
        $eventsCreated = 0;
        $eventsUpdated = 0;
        $eventsRemoved = 0;
        $httpCode = null;
        $errorMessage = null;

        try {
            $url = $connection->ical_url;
            if (empty($url)) {
                throw new \InvalidArgumentException('Calendar iCal URL is missing or invalid.');
            }

            $response = Http::timeout(20)
                ->retry(2, 500)
                ->withHeaders([
                    'User-Agent' => 'TourismBusinessManager/1.0 (Mozilla/5.0 compatible)',
                    'Accept' => 'text/calendar, text/plain, */*',
                ])
                ->get($url);

            $httpCode = $response->status();
            $durationMs = (int) round((microtime(true) - $startTime) * 1000);

            if (!$response->successful()) {
                throw new \RuntimeException("External calendar returned HTTP status {$httpCode}");
            }

            $icsData = $response->body();
            if (empty(trim($icsData))) {
                throw new \RuntimeException('Received empty calendar feed.');
            }

            $vcalendar = Reader::read($icsData);
            $vevents = $vcalendar->VEVENT ?? [];
            $eventsFound = count($vevents);

            $syncedUids = [];
            $businessTimezone = config('app.timezone', 'Asia/Colombo');

            foreach ($vevents as $event) {
                $uid = (string) ($event->UID ?? null);
                if (empty($uid)) {
                    continue;
                }
                $syncedUids[] = $uid;

                $dtStart = $event->DTSTART;
                $dtEnd = $event->DTEND;

                if (!$dtStart || !$dtEnd) {
                    continue;
                }

                $checkIn = Carbon::parse($dtStart->getDateTime())->setTimezone($businessTimezone)->toDateString();
                $checkOut = Carbon::parse($dtEnd->getDateTime())->setTimezone($businessTimezone)->toDateString();

                // Exclusive checkout calculation: checkout date is never counted as an occupied night
                $nights = Carbon::parse($checkIn)->diffInDays(Carbon::parse($checkOut));
                if ($nights < 1) {
                    $nights = 1; // Minimum 1 night if dates are identical
                }

                $summary = (string) ($event->SUMMARY ?? 'External Calendar Event');
                $statusProp = (string) ($event->STATUS ?? 'CONFIRMED');

                // Classify event status neutrally if insufficient info
                $status = match (strtoupper($statusProp)) {
                    'CONFIRMED' => 'confirmed',
                    'CANCELLED' => 'cancelled',
                    'TENTATIVE' => 'unknown',
                    default => 'unknown',
                };

                // Check for manually blocked availability keywords in summary
                if (stripos($summary, 'Not available') !== false || stripos($summary, 'Blocked') !== false) {
                    $status = 'blocked';
                }

                $rawMetadata = [
                    'summary' => $summary,
                    'status_raw' => $statusProp,
                    'description' => (string) ($event->DESCRIPTION ?? ''),
                    'dtstamp' => (string) ($event->DTSTAMP ?? ''),
                    'last_modified' => (string) ($event->{'LAST-MODIFIED'} ?? ''),
                ];

                $existing = CalendarEvent::where('calendar_connection_id', $connection->id)
                    ->where('source_event_uid', $uid)
                    ->first();

                if ($existing) {
                    $changed = false;
                    if ($existing->check_in_date->toDateString() !== $checkIn ||
                        $existing->check_out_date->toDateString() !== $checkOut ||
                        $existing->status !== $status ||
                        $existing->summary !== $summary) {
                        $changed = true;
                    }

                    $existing->update([
                        'summary' => $summary,
                        'check_in_date' => $checkIn,
                        'check_out_date' => $checkOut,
                        'booked_nights' => $nights,
                        'status' => $status,
                        'raw_metadata' => $rawMetadata,
                        'last_seen_in_feed_at' => now(),
                    ]);

                    if ($changed) {
                        $eventsUpdated++;
                    }

                    // Run deduplication and overlap check
                    $this->deduplicationService->evaluateEvent($existing);
                } else {
                    $newEvent = CalendarEvent::create([
                        'calendar_connection_id' => $connection->id,
                        'property_id' => $connection->property_id,
                        'room_id' => $connection->room_id,
                        'source_platform' => $connection->platform,
                        'source_event_uid' => $uid,
                        'summary' => $summary,
                        'check_in_date' => $checkIn,
                        'check_out_date' => $checkOut,
                        'booked_nights' => $nights,
                        'status' => $status,
                        'raw_metadata' => $rawMetadata,
                        'last_seen_in_feed_at' => now(),
                    ]);

                    $eventsCreated++;
                    $this->deduplicationService->evaluateEvent($newEvent);
                }
            }

            // Detect removed events (events previously in DB for this connection that are missing in the feed)
            if (!empty($syncedUids)) {
                $removedQuery = CalendarEvent::where('calendar_connection_id', $connection->id)
                    ->whereNotIn('source_event_uid', $syncedUids)
                    ->where('status', '!=', 'cancelled');

                $eventsRemoved = $removedQuery->count();
                $removedQuery->update(['status' => 'cancelled']);
            }

            $connection->update([
                'status' => 'active',
                'last_synced_at' => now(),
                'last_successful_sync_at' => now(),
                'last_error_message' => null,
                'last_http_code' => $httpCode,
            ]);

            CalendarSyncLog::create([
                'calendar_connection_id' => $connection->id,
                'started_at' => $startedAt,
                'finished_at' => now(),
                'status' => 'success',
                'events_found' => $eventsFound,
                'events_created' => $eventsCreated,
                'events_updated' => $eventsUpdated,
                'events_removed' => $eventsRemoved,
                'http_response_time_ms' => $durationMs,
                'error_message' => null,
                'created_at' => now(),
            ]);

            return [
                'success' => true,
                'events_found' => $eventsFound,
                'events_created' => $eventsCreated,
                'events_updated' => $eventsUpdated,
                'events_removed' => $eventsRemoved,
                'duration_ms' => $durationMs,
                'error' => null,
            ];
        } catch (\Throwable $e) {
            $durationMs = (int) round((microtime(true) - $startTime) * 1000);
            $errorMessage = $e->getMessage();

            Log::error("iCal Sync failed for connection {$connection->id}: {$errorMessage}", [
                'exception' => $e,
            ]);

            $connection->update([
                'status' => 'failed',
                'last_synced_at' => now(),
                'last_error_message' => $errorMessage,
                'last_http_code' => $httpCode,
            ]);

            CalendarSyncLog::create([
                'calendar_connection_id' => $connection->id,
                'started_at' => $startedAt,
                'finished_at' => now(),
                'status' => 'failed',
                'events_found' => $eventsFound,
                'events_created' => $eventsCreated,
                'events_updated' => $eventsUpdated,
                'events_removed' => $eventsRemoved,
                'http_response_time_ms' => $durationMs,
                'error_message' => $errorMessage,
                'created_at' => now(),
            ]);

            return [
                'success' => false,
                'events_found' => $eventsFound,
                'events_created' => $eventsCreated,
                'events_updated' => $eventsUpdated,
                'events_removed' => $eventsRemoved,
                'duration_ms' => $durationMs,
                'error' => $errorMessage,
            ];
        }
    }
}
