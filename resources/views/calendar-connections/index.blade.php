@extends('layouts.app')

@section('content')
<div class="space-y-8" x-data="calendarConnectionsManager()">
    <!-- Header -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">Calendar Connections & iCal Sync Hub</h1>
            <p class="text-xs text-slate-500 mt-0.5">Automated synchronization with Airbnb, Booking.com, and external OTA calendar export URLs.</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('calendar-connections.sync-all') }}" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold transition-colors flex items-center gap-1.5">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Sync All Calendars
            </a>
            <button @click="newModalOpen = true" class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-xs transition-colors flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Connect New iCal Feed
            </button>
        </div>
    </div>

    <!-- Potential Duplicates Review Panel (Section 5.7) -->
    @if($potentialMatches->isNotEmpty())
        <div class="bg-gradient-to-r from-amber-500/10 via-amber-500/5 to-transparent p-6 rounded-3xl border border-amber-300 shadow-xs space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <span class="text-lg">🔍</span>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900 tracking-tight">Cross-Platform Overlapping Reservations for Review</h2>
                        <p class="text-xs text-slate-600">The same room has overlapping dates across Airbnb and Booking.com feeds. Avoid double-counting in occupancy metrics.</p>
                    </div>
                </div>
                <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800">{{ $potentialMatches->count() }} Under Review</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($potentialMatches as $match)
                    <div class="p-4 rounded-2xl bg-white border border-amber-200/80 shadow-xs space-y-3 text-xs">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                            <span class="font-bold text-slate-900">{{ $match->room->property->name }} - {{ $match->room->name }}</span>
                            <span class="text-[11px] text-slate-400 font-mono">{{ $match->primaryEvent->check_in_date->format('d M') }} ➔ {{ $match->primaryEvent->check_out_date->format('d M Y') }}</span>
                        </div>

                        <div class="grid grid-cols-2 gap-3 text-[11px]">
                            <!-- Primary Event -->
                            <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                                <span class="font-bold uppercase tracking-wider block text-slate-700">{{ $match->primaryEvent->source_platform }}</span>
                                <span class="text-slate-500 truncate block mt-0.5">{{ $match->primaryEvent->summary }}</span>
                                <span class="text-emerald-700 font-semibold block mt-1">{{ $match->primaryEvent->booked_nights }} nights</span>
                            </div>
                            <!-- Matched Event -->
                            <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                                <span class="font-bold uppercase tracking-wider block text-slate-700">{{ $match->matchedEvent->source_platform }}</span>
                                <span class="text-slate-500 truncate block mt-0.5">{{ $match->matchedEvent->summary }}</span>
                                <span class="text-emerald-700 font-semibold block mt-1">{{ $match->matchedEvent->booked_nights }} nights</span>
                            </div>
                        </div>

                        <div class="pt-2 flex items-center justify-end gap-2">
                            <form method="POST" action="{{ route('calendar-connections.duplicates.conflict', [$match->primaryEvent->id, $match->matchedEvent->id]) }}">
                                @csrf
                                <button type="submit" class="px-2.5 py-1 text-[11px] font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg">
                                    Keep Separate (Real Conflict)
                                </button>
                            </form>
                            <form method="POST" action="{{ route('calendar-connections.duplicates.confirm', [$match->primaryEvent->id, $match->matchedEvent->id]) }}">
                                @csrf
                                <button type="submit" class="px-3 py-1 text-[11px] font-bold bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg shadow-2xs">
                                    ✓ Same Booking (Link Duplicate)
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Connections Table -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-100">
            <h2 class="text-base font-bold text-slate-900 tracking-tight">Active Calendar Feeds</h2>
            <p class="text-xs text-slate-500">Encrypted token URLs mapped to specific property units.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50/70 text-slate-400 font-semibold uppercase tracking-wider text-[10px]">
                    <tr>
                        <th class="py-3 px-4">Property & Room</th>
                        <th class="py-3 px-4">Platform</th>
                        <th class="py-3 px-4">Connection Name</th>
                        <th class="py-3 px-4">Calendar URL</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Last Sync</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($connections as $connection)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="py-3.5 px-4">
                                <span class="font-bold text-slate-900 block">{{ $connection->property->name }}</span>
                                <span class="text-[11px] text-slate-500">{{ $connection->room->name }}</span>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $connection->platform === 'airbnb' ? 'bg-rose-100 text-rose-700' : 'bg-blue-100 text-blue-700' }}">
                                    {{ $connection->platform }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 font-semibold text-slate-800">
                                {{ $connection->calendar_name }}
                            </td>
                            <td class="py-3.5 px-4 font-mono text-slate-400 text-[11px]">
                                {{ $connection->masked_url }}
                            </td>
                            <td class="py-3.5 px-4">
                                @if($connection->status === 'active')
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-100 text-emerald-800">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                                    </span>
                                @elseif($connection->status === 'paused')
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-slate-100 text-slate-600">
                                        Paused
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-rose-100 text-rose-800" title="{{ $connection->last_error_message }}">
                                        Failed
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-slate-500 text-[11px]">
                                {{ $connection->last_synced_at ? $connection->last_synced_at->diffForHumans() : 'Never' }}
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <form method="POST" action="{{ route('calendar-connections.sync', $connection->id) }}">
                                        @csrf
                                        <button type="submit" class="p-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold" title="Sync Now">
                                            Sync Now
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('calendar-connections.destroy', $connection->id) }}" onsubmit="return confirm('Delete this calendar connection?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg" title="Delete">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400 italic">No calendar connections created yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Sync History Logs -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs p-6 space-y-3">
        <h2 class="text-base font-bold text-slate-900 tracking-tight">Recent Synchronization Telemetry</h2>
        <div class="space-y-2">
            @forelse($recentLogs as $log)
                <div class="p-3 rounded-xl border border-slate-100 bg-slate-50 flex items-center justify-between text-xs">
                    <div class="flex items-center gap-3">
                        <span class="w-2 h-2 rounded-full {{ $log->status === 'success' ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                        <div>
                            <span class="font-bold text-slate-900">{{ $log->connection->calendar_name ?? 'Connection #' . $log->calendar_connection_id }}</span>
                            <span class="text-slate-500 text-[11px] block">Found: {{ $log->events_found }} | Created: {{ $log->events_created }} | Updated: {{ $log->events_updated }}</span>
                        </div>
                    </div>
                    <div class="text-right text-[11px] text-slate-400">
                        <span>{{ $log->http_response_time_ms ? $log->http_response_time_ms . 'ms' : '' }}</span>
                        <div class="text-slate-500">{{ $log->started_at ? $log->started_at->format('H:i:s d M') : '' }}</div>
                    </div>
                </div>
            @empty
                <p class="text-xs text-slate-400 italic py-2 text-center">No synchronization logs recorded yet.</p>
            @endforelse
        </div>
    </div>

    <!-- Modal: Connect New iCal Feed -->
    <div x-show="newModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 space-y-4 shadow-xl" @click.away="newModalOpen = false">
            <h3 class="text-base font-bold text-slate-900">Connect New Calendar Export Feed</h3>
            
            <form method="POST" action="{{ route('calendar-connections.store') }}" class="space-y-4">
                @csrf

                <!-- Property & Room Selection -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Property *</label>
                        <select name="property_id" x-model="selectedPropId" required class="w-full px-3 py-2 border rounded-xl text-xs">
                            <option value="">Select Property</option>
                            @foreach($properties as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Room *</label>
                        <select name="room_id" required class="w-full px-3 py-2 border rounded-xl text-xs">
                            <option value="">Select Room</option>
                            <template x-for="r in currentRooms" :key="r.id">
                                <option :value="r.id" x-text="r.name"></option>
                            </template>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">OTA Platform *</label>
                        <select name="platform" required class="w-full px-3 py-2 border rounded-xl text-xs">
                            <option value="airbnb">Airbnb</option>
                            <option value="booking_com">Booking.com</option>
                            <option value="other">Other iCal Feed</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Feed Label *</label>
                        <input type="text" name="calendar_name" required placeholder="e.g. Wild Pulse R1 Airbnb" class="w-full px-3 py-2 border rounded-xl text-xs" />
                    </div>
                </div>

                <!-- iCal URL Input with Test Connection Button -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">iCal Calendar Export URL *</label>
                    <div class="flex gap-2">
                        <input type="url" name="ical_url" x-model="testUrl" required placeholder="https://ical.booking.com/v1/export?t=... or airbnb.com/calendar/ical/..."
                               class="flex-1 px-3 py-2 border rounded-xl text-xs" />
                        <button type="button" @click="runUrlTest()" :disabled="testing || !testUrl" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold shrink-0">
                            <span x-show="!testing">Test Feed</span>
                            <span x-show="testing">Testing...</span>
                        </button>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-1">URLs are encrypted at rest with database column encryption.</p>
                </div>

                <!-- Test Result Feedback Banner -->
                <template x-if="testResult">
                    <div class="p-3 rounded-xl text-xs" :class="testResult.success ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200'">
                        <strong x-text="testResult.success ? '✓ Feed Verified Successfully!' : '✗ Feed Connection Failed'"></strong>
                        <div x-show="testResult.success" x-text="'Found ' + testResult.events_found + ' events in ' + testResult.duration_ms + 'ms.'"></div>
                        <div x-show="!testResult.success" x-text="testResult.error"></div>
                    </div>
                </template>

                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="newModalOpen = false" class="px-3 py-2 text-xs font-semibold text-slate-500">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-xs">
                        Save & Sync Feed
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function calendarConnectionsManager() {
    const rawProperties = @json($properties);
    return {
        newModalOpen: false,
        selectedPropId: '',
        testUrl: '',
        testing: false,
        testResult: null,

        get currentRooms() {
            if (!this.selectedPropId) return [];
            const prop = rawProperties.find(p => p.id == this.selectedPropId);
            return prop ? prop.rooms : [];
        },

        runUrlTest() {
            if (!this.testUrl) return;
            this.testing = true;
            this.testResult = null;

            fetch('{{ route("calendar-connections.test") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ ical_url: this.testUrl })
            })
            .then(res => res.json())
            .then(data => {
                this.testResult = data;
                this.testing = false;
            })
            .catch(err => {
                this.testResult = { success: false, error: err.message };
                this.testing = false;
            });
        }
    }
}
</script>
@endpush
@endsection
