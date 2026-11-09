@extends('layouts.app')

@section('content')
<div class="space-y-8" x-data="{ addRoomModal: false, maintenanceModal: false, activeRoomId: null, activeRoomName: '' }">
    <!-- Header Card -->
    <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-white font-bold text-lg shadow-sm" style="background-color: {{ $property->color_hex ?? '#2563EB' }}">
                {{ $property->code ?? substr($property->name, 0, 2) }}
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl font-bold text-slate-900 tracking-tight">{{ $property->name }}</h1>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $property->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500' }}">
                        {{ $property->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-1">{{ $property->address ?? 'No physical address recorded' }}</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button @click="addRoomModal = true" class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-xs transition-colors">
                + Add Room Unit
            </button>
            <a href="{{ route('properties.edit', $property->id) }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold transition-colors">
                Edit Details
            </a>
        </div>
    </div>

    <!-- Monthly Occupancy Overview for this Property -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
            <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">Occupancy Rate</span>
            <div class="mt-1 text-2xl font-bold text-blue-600">{{ $occupancy['occupancy_percentage'] }}%</div>
            <span class="text-[11px] text-slate-500 font-medium mt-1 block">Current Month</span>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
            <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">Booked Room Nights</span>
            <div class="mt-1 text-2xl font-bold text-slate-900">{{ $occupancy['booked_room_nights'] }}</div>
            <span class="text-[11px] text-slate-500 font-medium mt-1 block">Exclusive Checkouts</span>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
            <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">Available Nights</span>
            <div class="mt-1 text-2xl font-bold text-slate-900">{{ $occupancy['available_room_nights'] }}</div>
            <span class="text-[11px] text-slate-500 font-medium mt-1 block">Total Capacity</span>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
            <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">Maintenance Nights</span>
            <div class="mt-1 text-2xl font-bold text-slate-500">{{ $occupancy['maintenance_room_nights'] }}</div>
            <span class="text-[11px] text-slate-500 font-medium mt-1 block">Blocked Availability</span>
        </div>
    </div>

    <!-- Rooms and iCal Connections List -->
    <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div>
                <h2 class="text-base font-bold text-slate-900 tracking-tight">Accommodation Units & iCal Feeds</h2>
                <p class="text-xs text-slate-500">Each room can be synchronized with multiple Airbnb and Booking.com calendars.</p>
            </div>
            <span class="text-xs text-slate-400">{{ $property->rooms->count() }} Total Units</span>
        </div>

        <div class="space-y-4">
            @forelse($property->rooms as $room)
                <div class="p-4 rounded-2xl border border-slate-200/80 bg-slate-50/50 space-y-3">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div class="flex items-center gap-3">
                            <span class="w-2.5 h-2.5 rounded-full {{ $room->is_active ? 'bg-emerald-500' : 'bg-slate-300' }}"></span>
                            <div>
                                <h3 class="font-bold text-sm text-slate-900">{{ $room->name }}</h3>
                                <span class="text-xs text-slate-500">{{ $room->room_reference ?? 'No reference' }} • Max Occupancy: {{ $room->max_occupancy }} guests</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <button @click="maintenanceModal = true; activeRoomId = {{ $room->id }}; activeRoomName = '{{ $room->name }}'" class="px-2.5 py-1 text-xs font-semibold bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 rounded-lg shadow-2xs">
                                Block Maintenance Dates
                            </button>
                            <a href="{{ route('calendar-connections.index') }}" class="px-2.5 py-1 text-xs font-semibold bg-blue-50 text-blue-700 hover:bg-blue-100 rounded-lg">
                                Manage iCal Feeds ({{ $room->calendarConnections->count() }})
                            </a>
                        </div>
                    </div>

                    <!-- Room Connected Calendars -->
                    @if($room->calendarConnections->isNotEmpty())
                        <div class="pt-2 border-t border-slate-200/60 grid grid-cols-1 md:grid-cols-2 gap-2 text-xs">
                            @foreach($room->calendarConnections as $conn)
                                <div class="p-2.5 rounded-xl bg-white border border-slate-200/80 flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase {{ $conn->platform === 'airbnb' ? 'bg-rose-100 text-rose-700' : 'bg-blue-100 text-blue-700' }}">
                                            {{ $conn->platform }}
                                        </span>
                                        <span class="font-medium text-slate-800 truncate">{{ $conn->calendar_name }}</span>
                                    </div>
                                    <span class="text-[11px] text-slate-500">{{ $conn->last_synced_at ? $conn->last_synced_at->diffForHumans() : 'Never synced' }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @empty
                <p class="text-sm text-slate-400 italic py-6 text-center">No rooms registered for this property yet.</p>
            @endforelse
        </div>
    </div>

    <!-- Modal: Add Room -->
    <div x-show="addRoomModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 space-y-4 shadow-xl" @click.away="addRoomModal = false">
            <h3 class="text-base font-bold text-slate-900">Add Room to {{ $property->name }}</h3>
            <form method="POST" action="{{ route('properties.rooms.store', $property->id) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Room Name *</label>
                    <input type="text" name="name" required placeholder="e.g. Room 1, Deluxe Chalet" class="w-full px-3 py-2 border rounded-xl text-sm" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Room Reference (Optional)</label>
                    <input type="text" name="room_reference" placeholder="e.g. WP-R1" class="w-full px-3 py-2 border rounded-xl text-sm" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Max Occupancy</label>
                    <input type="number" name="max_occupancy" value="2" min="1" class="w-full px-3 py-2 border rounded-xl text-sm" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Description</label>
                    <textarea name="description" rows="2" class="w-full px-3 py-2 border rounded-xl text-sm"></textarea>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="addRoomModal = false" class="px-3 py-2 text-xs font-semibold text-slate-500">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded-xl text-xs font-bold">Save Room</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Block Maintenance Dates -->
    <div x-show="maintenanceModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 space-y-4 shadow-xl" @click.away="maintenanceModal = false">
            <h3 class="text-base font-bold text-slate-900">Block Maintenance for <span x-text="activeRoomName"></span></h3>
            <form :action="'{{ url('properties/rooms') }}/' + activeRoomId + '/maintenance'" method="POST" class="space-y-4">
                @csrf
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Start Date *</label>
                        <input type="date" name="start_date" required class="w-full px-3 py-2 border rounded-xl text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">End Date (Exclusive) *</label>
                        <input type="date" name="end_date" required class="w-full px-3 py-2 border rounded-xl text-sm" />
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Reason for block *</label>
                    <input type="text" name="reason" required placeholder="e.g. AC Servicing, Roof Painting" class="w-full px-3 py-2 border rounded-xl text-sm" />
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="maintenanceModal = false" class="px-3 py-2 text-xs font-semibold text-slate-500">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-slate-800 text-white rounded-xl text-xs font-bold">Save Maintenance Block</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
