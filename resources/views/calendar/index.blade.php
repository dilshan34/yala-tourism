@extends('layouts.app')

@section('content')
<div class="space-y-6" x-data="calendarManager()">
    <!-- Top Filter & Legend Bar -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold text-slate-900 tracking-tight">Central Business Calendar</h1>
                <p class="text-xs text-slate-500 mt-0.5">Unified schedule of Airbnb/Booking.com accommodations, safari excursions, and private transports.</p>
            </div>

            <!-- Global Action Triggers -->
            <div class="flex items-center gap-2">
                <a href="{{ route('calendar-connections.sync-all') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold transition-colors">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Refresh iCal Feeds
                </a>
            </div>
        </div>

        <!-- Filter Controls -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3 pt-3 border-t border-slate-100">
            <!-- Property Filter -->
            <div>
                <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Property</label>
                <select x-model="selectedProperty" @change="refetchCalendar()" class="w-full px-2.5 py-1.5 rounded-xl border border-slate-200 text-xs text-slate-800 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                    <option value="">All Properties</option>
                    @foreach($properties as $prop)
                        <option value="{{ $prop->id }}">{{ $prop->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Platform Filter -->
            <div>
                <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">OTA Platform</label>
                <select x-model="selectedPlatform" @change="refetchCalendar()" class="w-full px-2.5 py-1.5 rounded-xl border border-slate-200 text-xs text-slate-800 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                    <option value="all">All Channels</option>
                    <option value="airbnb">Airbnb iCal</option>
                    <option value="booking_com">Booking.com iCal</option>
                    <option value="other">Other Sources</option>
                </select>
            </div>

            <!-- Service Category Filter -->
            <div>
                <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Service Category</label>
                <select x-model="selectedServiceType" @change="refetchCalendar()" class="w-full px-2.5 py-1.5 rounded-xl border border-slate-200 text-xs text-slate-800 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                    <option value="all">All Services</option>
                    <option value="accommodation">Accommodation Only</option>
                    <option value="safari">Safaris Only</option>
                    <option value="transport">Transports Only</option>
                    <option value="maintenance">Maintenance Only</option>
                </select>
            </div>

            <!-- Status Filter -->
            <div>
                <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Operational Status</label>
                <select x-model="selectedStatus" @change="refetchCalendar()" class="w-full px-2.5 py-1.5 rounded-xl border border-slate-200 text-xs text-slate-800 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                    <option value="all">All Statuses</option>
                    <option value="pending">Pending Services</option>
                    <option value="completed">Completed Services</option>
                </select>
            </div>

            <!-- Color Palette Legend -->
            <div class="col-span-2 md:col-span-1 flex flex-col justify-end">
                <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Legend</span>
                <div class="flex flex-wrap gap-2 text-[10px] font-medium text-slate-600">
                    <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span> Wild Pulse</span>
                    <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-purple-600"></span> Yala Bliss</span>
                    <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-emerald-600"></span> Safari</span>
                    <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-amber-600"></span> Transport</span>
                    <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-slate-500"></span> Blocked</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Calendar Container -->
    <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs">
        <div id="fullcalendar" class="min-h-[700px]"></div>
    </div>

    <!-- Event Detail Slide-over Modal -->
    <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 overflow-hidden" @keydown.escape.window="modalOpen = false">
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity" @click="modalOpen = false"></div>
        <div class="fixed inset-y-0 right-0 max-w-md w-full bg-white shadow-2xl flex flex-col z-50">
            <!-- Modal Header -->
            <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider px-2 py-0.5 rounded text-white" :style="'background-color: ' + activeEventColor">
                        <span x-text="activeEventProps.type"></span>
                    </span>
                    <h3 class="text-lg font-bold text-slate-900 mt-2 truncate" x-text="activeEventTitle"></h3>
                </div>
                <button @click="modalOpen = false" class="p-2 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Modal Body Details -->
            <div class="p-6 flex-1 overflow-y-auto space-y-5 text-sm">
                <!-- Accommodation Event Display -->
                <template x-if="activeEventProps.type === 'accommodation'">
                    <div class="space-y-4">
                        <div class="p-3.5 rounded-xl bg-blue-50/60 border border-blue-100 space-y-2">
                            <div class="text-xs font-semibold text-blue-800 uppercase tracking-wider">iCal Imported OTA Reservation</div>
                            <div class="grid grid-cols-2 gap-2 text-xs">
                                <div><span class="text-slate-400">Property:</span> <strong x-text="activeEventProps.property_name"></strong></div>
                                <div><span class="text-slate-400">Room:</span> <strong x-text="activeEventProps.room_name"></strong></div>
                                <div><span class="text-slate-400">Platform:</span> <strong x-text="activeEventProps.platform"></strong></div>
                                <div><span class="text-slate-400">Source Feed:</span> <span class="truncate block text-slate-600" x-text="activeEventProps.connection_name"></span></div>
                            </div>
                        </div>

                        <div class="space-y-2 text-xs">
                            <div class="flex justify-between py-1.5 border-b border-slate-100">
                                <span class="text-slate-500">Check-in:</span>
                                <strong class="text-slate-900" x-text="activeEventProps.check_in"></strong>
                            </div>
                            <div class="flex justify-between py-1.5 border-b border-slate-100">
                                <span class="text-slate-500">Check-out (Exclusive):</span>
                                <strong class="text-slate-900" x-text="activeEventProps.check_out"></strong>
                            </div>
                            <div class="flex justify-between py-1.5 border-b border-slate-100">
                                <span class="text-slate-500">Booked Nights:</span>
                                <strong class="text-emerald-700" x-text="activeEventProps.booked_nights + ' nights'"></strong>
                            </div>
                            <div class="flex justify-between py-1.5 border-b border-slate-100">
                                <span class="text-slate-500">Event Status:</span>
                                <span class="font-semibold text-slate-800" x-text="activeEventProps.status"></span>
                            </div>
                            <div class="pt-2">
                                <span class="text-slate-400 block mb-1">Calendar Summary:</span>
                                <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-700 italic" x-text="activeEventProps.summary"></div>
                            </div>
                        </div>

                        <template x-if="activeEventProps.conflict">
                            <div class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold">
                                ⚠️ Scheduling Conflict Detected: This room has overlapping dates across external platforms. Review in Calendar Connections.
                            </div>
                        </template>

                        <div class="pt-4">
                            <a href="{{ route('calendar-connections.index') }}" class="block w-full py-2 px-3 text-center bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold transition-colors">
                                Manage Calendar Connections & Duplicates
                            </a>
                        </div>
                    </div>
                </template>

                <!-- Safari Event Display -->
                <template x-if="activeEventProps.type === 'safari'">
                    <div class="space-y-4">
                        <div class="p-3.5 rounded-xl bg-emerald-50/60 border border-emerald-100 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-emerald-800 uppercase tracking-wider" x-text="activeEventProps.reference_no"></span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-100 text-emerald-800" x-text="activeEventProps.operational_status"></span>
                            </div>
                            <div class="text-sm font-bold text-slate-900" x-text="activeEventProps.guest_name"></div>
                            <div class="text-xs text-slate-600" x-text="activeEventProps.guest_phone"></div>
                        </div>

                        <div class="space-y-2 text-xs">
                            <div class="flex justify-between py-1.5 border-b border-slate-100">
                                <span class="text-slate-500">Property:</span>
                                <strong class="text-slate-900" x-text="activeEventProps.property_name"></strong>
                            </div>
                            <div class="flex justify-between py-1.5 border-b border-slate-100">
                                <span class="text-slate-500">Date & Pickup:</span>
                                <strong class="text-slate-900" x-text="activeEventProps.safari_date + ' at ' + activeEventProps.pickup_time"></strong>
                            </div>
                            <div class="flex justify-between py-1.5 border-b border-slate-100">
                                <span class="text-slate-500">Pickup Location:</span>
                                <span class="text-slate-900 text-right truncate" x-text="activeEventProps.pickup_location"></span>
                            </div>
                            <div class="flex justify-between py-1.5 border-b border-slate-100">
                                <span class="text-slate-500">Type / Category:</span>
                                <span class="text-slate-900" x-text="activeEventProps.safari_type + ' (' + activeEventProps.safari_category + ')'"></span>
                            </div>
                            <div class="flex justify-between py-1.5 border-b border-slate-100">
                                <span class="text-slate-500">Assigned Driver:</span>
                                <span class="text-slate-900" x-text="activeEventProps.driver + ' (' + activeEventProps.driver_phone + ')'"></span>
                            </div>
                            <div class="flex justify-between py-1.5 border-b border-slate-100">
                                <span class="text-slate-500">Selling Price:</span>
                                <strong class="text-slate-900" x-text="activeEventProps.selling_price"></strong>
                            </div>
                            <div class="flex justify-between py-1.5 border-b border-slate-100">
                                <span class="text-slate-500">Amount Paid:</span>
                                <strong class="text-emerald-600" x-text="activeEventProps.amount_paid"></strong>
                            </div>
                        </div>

                        <div class="pt-4">
                            <a :href="activeEventProps.view_url" class="block w-full py-2.5 px-4 text-center bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-xs transition-colors">
                                Open Full Safari Details & Record Payment →
                            </a>
                        </div>
                    </div>
                </template>

                <!-- Transport Event Display -->
                <template x-if="activeEventProps.type === 'transport'">
                    <div class="space-y-4">
                        <div class="p-3.5 rounded-xl bg-amber-50/60 border border-amber-100 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-amber-800 uppercase tracking-wider" x-text="activeEventProps.reference_no"></span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-amber-100 text-amber-800" x-text="activeEventProps.operational_status"></span>
                            </div>
                            <div class="text-sm font-bold text-slate-900" x-text="activeEventProps.guest_name"></div>
                            <div class="text-xs text-slate-600" x-text="activeEventProps.guest_phone"></div>
                        </div>

                        <div class="space-y-2 text-xs">
                            <div class="flex justify-between py-1.5 border-b border-slate-100">
                                <span class="text-slate-500">Property:</span>
                                <strong class="text-slate-900" x-text="activeEventProps.property_name"></strong>
                            </div>
                            <div class="flex justify-between py-1.5 border-b border-slate-100">
                                <span class="text-slate-500">Date & Pickup:</span>
                                <strong class="text-slate-900" x-text="activeEventProps.transport_date + ' at ' + activeEventProps.pickup_time"></strong>
                            </div>
                            <div class="flex justify-between py-1.5 border-b border-slate-100">
                                <span class="text-slate-500">Pickup Location:</span>
                                <span class="text-slate-900 text-right truncate" x-text="activeEventProps.pickup_location"></span>
                            </div>
                            <div class="flex justify-between py-1.5 border-b border-slate-100">
                                <span class="text-slate-500">Drop-off Destination:</span>
                                <span class="text-slate-900 text-right truncate" x-text="activeEventProps.drop_off_location"></span>
                            </div>
                            <div class="flex justify-between py-1.5 border-b border-slate-100">
                                <span class="text-slate-500">Driver & Vehicle:</span>
                                <span class="text-slate-900" x-text="activeEventProps.driver + ' | ' + activeEventProps.vehicle"></span>
                            </div>
                            <div class="flex justify-between py-1.5 border-b border-slate-100">
                                <span class="text-slate-500">Selling Price:</span>
                                <strong class="text-slate-900" x-text="activeEventProps.selling_price"></strong>
                            </div>
                            <div class="flex justify-between py-1.5 border-b border-slate-100">
                                <span class="text-slate-500">Amount Paid:</span>
                                <strong class="text-emerald-600" x-text="activeEventProps.amount_paid"></strong>
                            </div>
                        </div>

                        <div class="pt-4">
                            <a :href="activeEventProps.view_url" class="block w-full py-2.5 px-4 text-center bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-semibold shadow-xs transition-colors">
                                Open Full Transport Details & Record Payment →
                            </a>
                        </div>
                    </div>
                </template>

                <!-- Maintenance Event Display -->
                <template x-if="activeEventProps.type === 'maintenance'">
                    <div class="space-y-4">
                        <div class="p-3.5 rounded-xl bg-slate-100 border border-slate-200 space-y-2">
                            <div class="text-xs font-bold text-slate-800 uppercase tracking-wider">Room Block / Maintenance</div>
                            <div class="text-sm font-semibold text-slate-900" x-text="activeEventProps.property_name + ' - ' + activeEventProps.room_name"></div>
                        </div>

                        <div class="space-y-2 text-xs">
                            <div class="flex justify-between py-1.5 border-b border-slate-100">
                                <span class="text-slate-500">Dates Blocked:</span>
                                <strong class="text-slate-900" x-text="activeEventProps.start_date + ' to ' + activeEventProps.end_date"></strong>
                            </div>
                            <div class="pt-2">
                                <span class="text-slate-400 block mb-1">Reason:</span>
                                <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-700" x-text="activeEventProps.reason"></div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function calendarManager() {
    return {
        selectedProperty: '',
        selectedPlatform: 'all',
        selectedServiceType: 'all',
        selectedStatus: 'all',
        modalOpen: false,
        activeEventTitle: '',
        activeEventColor: '#2563EB',
        activeEventProps: {},
        calendarInstance: null,

        init() {
            const calendarEl = document.getElementById('fullcalendar');
            if (!calendarEl || typeof window.FullCalendar === 'undefined') return;

            const self = this;
            const { Calendar, dayGridPlugin, timeGridPlugin, listPlugin } = window.FullCalendar;

            this.calendarInstance = new Calendar(calendarEl, {
                plugins: [dayGridPlugin, timeGridPlugin, listPlugin],
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
                },
                buttonText: {
                    today: 'Today',
                    month: 'Month',
                    week: 'Week',
                    day: 'Day',
                    list: 'Agenda / List'
                },
                events: function(fetchInfo, successCallback, failureCallback) {
                    const params = new URLSearchParams({
                        start: fetchInfo.startStr,
                        end: fetchInfo.endStr,
                        property_id: self.selectedProperty,
                        platform: self.selectedPlatform,
                        service_type: self.selectedServiceType,
                        status: self.selectedStatus,
                    });

                    fetch('{{ route("calendar.events") }}?' + params.toString())
                        .then(response => response.json())
                        .then(data => successCallback(data))
                        .catch(error => {
                            console.error('Failed to load events:', error);
                            failureCallback(error);
                        });
                },
                eventClick: function(info) {
                    self.activeEventTitle = info.event.title;
                    self.activeEventColor = info.event.backgroundColor || '#2563EB';
                    self.activeEventProps = info.event.extendedProps || {};
                    self.modalOpen = true;
                },
                height: 'auto',
                aspectRatio: 1.8,
                nowIndicator: true,
                dayMaxEvents: 4,
            });

            this.calendarInstance.render();
        },

        refetchCalendar() {
            if (this.calendarInstance) {
                this.calendarInstance.refetchEvents();
            }
        }
    }
}
</script>
@endpush
@endsection
