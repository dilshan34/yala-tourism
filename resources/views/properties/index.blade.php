@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">Properties & Accommodations</h1>
            <p class="text-xs text-slate-500 mt-0.5">Manage your villas, eco-lodges, rooms, and maintenance blocks.</p>
        </div>

        <div class="flex items-center gap-3 w-full md:w-auto">
            <form method="GET" action="{{ route('properties.index') }}" class="relative flex-1 md:w-64">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search property..." class="w-full pl-9 pr-3 py-1.5 rounded-xl border border-slate-200 text-xs bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20" />
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </form>

            <a href="{{ route('properties.create') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-xs transition-colors shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add Property
            </a>
        </div>
    </div>

    <!-- Properties Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($properties as $property)
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden flex flex-col">
                <div class="h-3 w-full" style="background-color: {{ $property->color_hex ?? '#2563EB' }}"></div>
                <div class="p-6 flex-1 flex flex-col justify-between space-y-4">
                    <div>
                        <div class="flex items-start justify-between">
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ $property->code ?? 'PROP' }}</span>
                                <h3 class="text-lg font-bold text-slate-900 tracking-tight">{{ $property->name }}</h3>
                            </div>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $property->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500' }}">
                                {{ $property->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-2 line-clamp-2">{{ $property->description ?? 'No description provided.' }}</p>
                    </div>

                    <!-- Rooms Summary -->
                    <div class="pt-3 border-t border-slate-100">
                        <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block mb-2">Rooms ({{ $property->rooms->count() }})</span>
                        <div class="space-y-1.5">
                            @forelse($property->rooms as $room)
                                <div class="p-2 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-between text-xs">
                                    <span class="font-medium text-slate-800">{{ $room->name }}</span>
                                    <div class="flex items-center gap-2">
                                        <span class="text-[11px] text-slate-500 font-medium">{{ $room->calendar_connections_count }} iCal feeds</span>
                                        <span class="w-2 h-2 rounded-full {{ $room->is_active ? 'bg-emerald-500' : 'bg-slate-300' }}"></span>
                                    </div>
                                </div>
                            @empty
                                <p class="text-xs text-slate-400 italic">No rooms added yet.</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="pt-2 flex items-center justify-between">
                        <a href="{{ route('properties.show', $property->id) }}" class="text-xs font-bold text-emerald-600 hover:text-emerald-700 flex items-center gap-1">
                            Manage Rooms & Availability →
                        </a>
                        <form method="POST" action="{{ route('properties.toggle-active', $property->id) }}">
                            @csrf
                            <button type="submit" class="text-[11px] font-semibold text-slate-400 hover:text-slate-600">
                                {{ $property->is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white p-12 text-center rounded-3xl border border-slate-200">
                <p class="text-sm text-slate-500">No properties found matching your criteria.</p>
            </div>
        @endforelse
    </div>

    {{ $properties->links() }}
</div>
@endsection
