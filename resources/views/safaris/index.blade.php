@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header & Action Bar -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">Yala Safari Management</h1>
            <p class="text-xs text-slate-500 mt-0.5">Independent wildlife safari bookings, jeep assignments, costs, and profit tracking.</p>
        </div>

        <div class="flex items-center gap-3 w-full md:w-auto">
            <a href="{{ route('safaris.create') }}" class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-xs transition-colors flex items-center gap-1.5 shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Book New Safari
            </a>
        </div>
    </div>

    <!-- Filters Bar -->
    <form method="GET" action="{{ route('safaris.index') }}" class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs grid grid-cols-2 md:grid-cols-5 gap-3">
        <!-- Search -->
        <div>
            <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Search</label>
            <input type="text" name="search" value="{{ $search }}" placeholder="Ref, guest, driver..." class="w-full px-3 py-1.5 rounded-xl border border-slate-200 text-xs bg-slate-50" />
        </div>

        <!-- Date Filter -->
        <div>
            <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Date Window</label>
            <select name="date_filter" onchange="this.form.submit()" class="w-full px-3 py-1.5 rounded-xl border border-slate-200 text-xs bg-slate-50">
                <option value="all">All Dates</option>
                <option value="today" {{ $dateFilter === 'today' ? 'selected' : '' }}>Today's Safaris</option>
                <option value="tomorrow" {{ $dateFilter === 'tomorrow' ? 'selected' : '' }}>Tomorrow's Safaris</option>
                <option value="this_month" {{ $dateFilter === 'this_month' ? 'selected' : '' }}>This Month</option>
            </select>
        </div>

        <!-- Status Filter -->
        <div>
            <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Operational Status</label>
            <select name="status" onchange="this.form.submit()" class="w-full px-3 py-1.5 rounded-xl border border-slate-200 text-xs bg-slate-50">
                <option value="all">All Statuses</option>
                <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="confirmed" {{ $status === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                <option value="in_progress" {{ $status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                <option value="completed" {{ $status === 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="cancelled" {{ $status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>
        </div>

        <!-- Property Association Filter -->
        <div>
            <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Property Link</label>
            <select name="property_id" onchange="this.form.submit()" class="w-full px-3 py-1.5 rounded-xl border border-slate-200 text-xs bg-slate-50">
                <option value="all">All Properties & External</option>
                <option value="external" {{ $propertyId === 'external' ? 'selected' : '' }}>External / No Accommodation</option>
                @foreach($properties as $prop)
                    <option value="{{ $prop->id }}" {{ $propertyId == $prop->id ? 'selected' : '' }}>{{ $prop->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex items-end">
            <button type="submit" class="w-full py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold">
                Apply Filters
            </button>
        </div>
    </form>

    <!-- Safari Bookings Table -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50/70 text-slate-400 font-semibold uppercase tracking-wider text-[10px]">
                    <tr>
                        <th class="py-3 px-4">Ref & Guest</th>
                        <th class="py-3 px-4">Date & Pickup</th>
                        <th class="py-3 px-4">Type & Park Block</th>
                        <th class="py-3 px-4">Property Link</th>
                        <th class="py-3 px-4">Driver & Jeep</th>
                        <th class="py-3 px-4">Financials (LKR)</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($safaris as $safari)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="py-3.5 px-4">
                                <a href="{{ route('safaris.show', $safari->id) }}" class="font-bold text-slate-900 hover:text-emerald-600 block">
                                    {{ $safari->reference_no }}
                                </a>
                                <span class="font-medium text-slate-700 block mt-0.5">{{ $safari->primaryGuest->full_name }}</span>
                                <span class="text-[11px] text-slate-400">{{ $safari->primaryGuest->phone_number }}</span>
                            </td>
                            <td class="py-3.5 px-4">
                                <strong class="text-slate-900 block">{{ $safari->safari_date->format('d M Y') }}</strong>
                                <span class="text-[11px] text-slate-500 font-medium block">⏰ {{ $safari->pickup_time }}</span>
                                <span class="text-[11px] text-slate-400 truncate block max-w-xs">{{ $safari->pickup_location }}</span>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="font-semibold text-slate-800 block">{{ ucwords(str_replace('_', ' ', $safari->safari_type)) }}</span>
                                <span class="text-[11px] text-slate-500 capitalize">{{ $safari->safari_category }} ({{ $safari->guest_count }} pax)</span>
                                <span class="text-[11px] text-slate-400 block">{{ $safari->national_park_block }}</span>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold {{ $safari->property_id ? 'bg-blue-50 text-blue-700' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $safari->effective_property_name }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="font-medium text-slate-800 block">{{ $safari->driver_name ?? ($safari->driver->name ?? 'Unassigned') }}</span>
                                <span class="text-[11px] text-slate-400 block">{{ $safari->jeep_details ?? 'Jeep unassigned' }}</span>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="font-semibold text-slate-900">Price: {{ number_format($safari->selling_price) }}</div>
                                <div class="text-[11px] text-emerald-600 font-medium">
                                    @if($safari->profit !== null)
                                        Profit: {{ number_format($safari->profit) }}
                                    @else
                                        <span class="text-amber-600 italic">Cost Unrecorded</span>
                                    @endif
                                </div>
                                <div class="text-[10px] text-slate-400">Paid: {{ number_format($safari->amount_paid) }}</div>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider {{ match($safari->operational_status) {
                                    'pending' => 'bg-amber-100 text-amber-800',
                                    'confirmed' => 'bg-blue-100 text-blue-800',
                                    'in_progress' => 'bg-indigo-100 text-indigo-800',
                                    'completed' => 'bg-emerald-100 text-emerald-800',
                                    'cancelled' => 'bg-rose-100 text-rose-800',
                                    default => 'bg-slate-100 text-slate-700',
                                } }}">
                                    {{ $safari->operational_status }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <a href="{{ route('safaris.show', $safari->id) }}" class="p-1.5 text-slate-600 hover:text-emerald-600 font-semibold text-xs">
                                    Details →
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-10 text-center text-slate-400 italic">No safaris found matching your criteria.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $safaris->links() }}
</div>
@endsection
