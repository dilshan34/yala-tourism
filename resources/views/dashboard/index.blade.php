@extends('layouts.app')

@section('content')
<div class="space-y-8" x-data="{ customDateModal: false }">
    <!-- Top Filter Header -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">Executive Operations & Analytics</h1>
            <p class="text-xs text-slate-500 mt-0.5">Central overview of accommodations, safari expeditions, and transport services.</p>
        </div>

        <form method="GET" action="{{ route('dashboard') }}" class="flex flex-wrap items-center gap-2 w-full md:w-auto">
            <!-- Property Selector -->
            <select name="property_id" onchange="this.form.submit()" class="px-3 py-1.5 rounded-xl border border-slate-200 text-xs font-medium text-slate-700 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                <option value="">All Properties ({{ $properties->count() }})</option>
                @foreach($properties as $prop)
                    <option value="{{ $prop->id }}" {{ $propertyId == $prop->id ? 'selected' : '' }}>
                        {{ $prop->name }} ({{ $prop->rooms_count }} rooms)
                    </option>
                @endforeach
            </select>

            <!-- Time Filter Pills -->
            <div class="flex items-center bg-slate-100 p-1 rounded-xl text-xs font-medium text-slate-600">
                <a href="{{ route('dashboard', ['filter' => 'today', 'property_id' => $propertyId]) }}" class="px-2.5 py-1 rounded-lg transition-colors {{ $timeFilter === 'today' ? 'bg-white text-emerald-700 font-semibold shadow-xs' : 'hover:text-slate-900' }}">Today</a>
                <a href="{{ route('dashboard', ['filter' => 'this_week', 'property_id' => $propertyId]) }}" class="px-2.5 py-1 rounded-lg transition-colors {{ $timeFilter === 'this_week' ? 'bg-white text-emerald-700 font-semibold shadow-xs' : 'hover:text-slate-900' }}">This Week</a>
                <a href="{{ route('dashboard', ['filter' => 'this_month', 'property_id' => $propertyId]) }}" class="px-2.5 py-1 rounded-lg transition-colors {{ $timeFilter === 'this_month' ? 'bg-white text-emerald-700 font-semibold shadow-xs' : 'hover:text-slate-900' }}">This Month</a>
                <a href="{{ route('dashboard', ['filter' => 'this_year', 'property_id' => $propertyId]) }}" class="px-2.5 py-1 rounded-lg transition-colors {{ $timeFilter === 'this_year' ? 'bg-white text-emerald-700 font-semibold shadow-xs' : 'hover:text-slate-900' }}">This Year</a>
            </div>
        </form>
    </div>

    <!-- 15.1 Top KPI Cards -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <!-- Properties & Rooms -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
            <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">Properties / Rooms</span>
            <div class="mt-1 flex items-baseline gap-1.5">
                <span class="text-2xl font-bold text-slate-900">{{ $totalProperties }}</span>
                <span class="text-xs text-slate-500 font-medium">/ {{ $totalRooms }} rooms</span>
            </div>
            <span class="text-[11px] text-emerald-600 font-medium mt-1 block">Active Portfolio</span>
        </div>

        <!-- Occupied Rooms Today -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
            <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">Occupied Today</span>
            <div class="mt-1 flex items-baseline gap-1.5">
                <span class="text-2xl font-bold text-blue-600">{{ $currentOccupiedRooms }}</span>
                <span class="text-xs text-slate-500 font-medium">/ {{ $totalRooms }} ({{ $totalRooms > 0 ? round(($currentOccupiedRooms/$totalRooms)*100) : 0 }}%)</span>
            </div>
            <span class="text-[11px] text-slate-500 font-medium mt-1 block">Live Occupancy</span>
        </div>

        <!-- Today's In / Out -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
            <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">Today Check-In / Out</span>
            <div class="mt-1 flex items-baseline gap-2">
                <span class="text-xl font-bold text-emerald-600">↓ {{ $todayCheckIns->count() }}</span>
                <span class="text-slate-300">|</span>
                <span class="text-xl font-bold text-amber-600">↑ {{ $todayCheckOuts->count() }}</span>
            </div>
            <span class="text-[11px] text-slate-500 font-medium mt-1 block">Imported iCal Feeds</span>
        </div>

        <!-- Total Revenue -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
            <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">Period Revenue</span>
            <div class="mt-1 text-2xl font-bold text-slate-900 truncate">
                {{ number_format($finance['total_revenue']) }}
            </div>
            <span class="text-[11px] text-slate-500 font-medium mt-1 block">{{ $finance['currency'] }} (Safari + Transfer)</span>
        </div>

        <!-- Total Profit -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
            <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">Realized Profit</span>
            <div class="mt-1 text-2xl font-bold text-emerald-600 truncate">
                {{ number_format($finance['total_profit']) }}
            </div>
            <span class="text-[11px] text-emerald-700/80 font-medium mt-1 block">Revenue - Recorded Costs</span>
        </div>

        <!-- Outstanding Payments -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
            <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">Outstanding Balances</span>
            <div class="mt-1 text-2xl font-bold {{ $finance['total_outstanding'] > 0 ? 'text-rose-600' : 'text-slate-900' }} truncate">
                {{ number_format($finance['total_outstanding']) }}
            </div>
            <span class="text-[11px] text-slate-500 font-medium mt-1 block">{{ $finance['currency'] }} Uncollected</span>
        </div>
    </div>

    <!-- 15.5 TOMORROW'S ACTIVITIES (Most Critical Operational Section) -->
    <div class="bg-gradient-to-r from-amber-500/10 via-amber-500/5 to-transparent p-6 rounded-3xl border border-amber-300/80 shadow-xs">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-amber-500 text-white flex items-center justify-center shadow-md shadow-amber-500/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-slate-900 tracking-tight">Tomorrow's Operational Master Plan</h2>
                    <p class="text-xs text-slate-600">Essential preparations scheduled for tomorrow: {{ now()->addDay()->format('l, d F Y') }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 bg-amber-100 text-amber-800 rounded-lg text-xs font-semibold">
                    {{ $tomorrowSafaris->count() }} Safaris
                </span>
                <span class="px-2.5 py-1 bg-amber-100 text-amber-800 rounded-lg text-xs font-semibold">
                    {{ $tomorrowTransports->count() }} Transports
                </span>
                <span class="px-2.5 py-1 bg-blue-100 text-blue-800 rounded-lg text-xs font-semibold">
                    {{ $tomorrowCheckIns->count() }} Check-ins
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            <!-- Tomorrow's Safaris -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <span class="text-xs font-bold text-emerald-800 uppercase tracking-wider flex items-center gap-1.5">
                        🦁 Tomorrow's Safaris ({{ $tomorrowSafaris->count() }})
                    </span>
                    <a href="{{ route('safaris.index', ['date_filter' => 'tomorrow']) }}" class="text-[11px] font-semibold text-emerald-600 hover:text-emerald-700">View All →</a>
                </div>

                @forelse($tomorrowSafaris as $safari)
                    <div class="p-3.5 rounded-xl border {{ $safari->operational_status === 'pending' ? 'bg-amber-50/50 border-amber-200' : 'bg-slate-50 border-slate-200' }} space-y-2">
                        <div class="flex items-start justify-between">
                            <div>
                                <a href="{{ route('safaris.show', $safari->id) }}" class="text-xs font-bold text-slate-900 hover:text-emerald-600">
                                    {{ $safari->primaryGuest->full_name }}
                                </a>
                                <div class="text-[11px] text-slate-500 font-medium">{{ $safari->primaryGuest->phone_number }}</div>
                            </div>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $safari->operational_status === 'pending' ? 'bg-amber-100 text-amber-800 animate-pulse' : 'bg-emerald-100 text-emerald-800' }}">
                                {{ $safari->operational_status }}
                            </span>
                        </div>

                        <div class="text-xs grid grid-cols-2 gap-1 text-slate-600 pt-1 border-t border-slate-200/60">
                            <div><span class="text-slate-400">Pickup:</span> {{ $safari->pickup_time }}</div>
                            <div><span class="text-slate-400">Type:</span> {{ ucwords(str_replace('_', ' ', $safari->safari_type)) }}</div>
                            <div class="col-span-2 truncate"><span class="text-slate-400">Location:</span> {{ $safari->pickup_location }}</div>
                            <div class="col-span-2"><span class="text-slate-400">Driver/Jeep:</span> {{ $safari->driver_name ?? ($safari->driver->name ?? 'Unassigned') }} ({{ $safari->driver_phone ?? 'No phone' }})</div>
                        </div>

                        @if($safari->operational_status === 'pending')
                            <form method="POST" action="{{ route('safaris.status', $safari->id) }}" class="pt-1">
                                @csrf
                                <input type="hidden" name="status" value="confirmed">
                                <button type="submit" class="w-full py-1 text-center bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold transition-colors">
                                    Confirm Safari Now
                                </button>
                            </form>
                        @endif
                    </div>
                @empty
                    <p class="text-xs text-slate-400 italic py-4 text-center">No safaris scheduled for tomorrow.</p>
                @endforelse
            </div>

            <!-- Tomorrow's Transports -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <span class="text-xs font-bold text-amber-800 uppercase tracking-wider flex items-center gap-1.5">
                        🚐 Tomorrow's Transports ({{ $tomorrowTransports->count() }})
                    </span>
                    <a href="{{ route('transports.index', ['date_filter' => 'tomorrow']) }}" class="text-[11px] font-semibold text-amber-600 hover:text-amber-700">View All →</a>
                </div>

                @forelse($tomorrowTransports as $trn)
                    <div class="p-3.5 rounded-xl border {{ in_array($trn->operational_status, ['pending', 'driver_assigned']) ? 'bg-amber-50/50 border-amber-200' : 'bg-slate-50 border-slate-200' }} space-y-2">
                        <div class="flex items-start justify-between">
                            <div>
                                <a href="{{ route('transports.show', $trn->id) }}" class="text-xs font-bold text-slate-900 hover:text-amber-600">
                                    {{ $trn->primaryGuest->full_name }}
                                </a>
                                <div class="text-[11px] text-slate-500 font-medium">{{ $trn->primaryGuest->phone_number }}</div>
                            </div>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-amber-100 text-amber-800">
                                {{ str_replace('_', ' ', $trn->operational_status) }}
                            </span>
                        </div>

                        <div class="text-xs space-y-1 text-slate-600 pt-1 border-t border-slate-200/60">
                            <div><span class="text-slate-400">Pickup:</span> {{ $trn->pickup_time }} at {{ $trn->pickup_location }}</div>
                            <div><span class="text-slate-400">Destination:</span> {{ $trn->drop_off_location }}</div>
                            <div><span class="text-slate-400">Driver & Vehicle:</span> {{ $trn->driver_name ?? ($trn->driver->name ?? 'Unassigned') }} | {{ $trn->vehicle->registration_number ?? 'Vehicle unassigned' }}</div>
                        </div>

                        @if($trn->operational_status === 'pending')
                            <form method="POST" action="{{ route('transports.status', $trn->id) }}" class="pt-1">
                                @csrf
                                <input type="hidden" name="status" value="confirmed">
                                <button type="submit" class="w-full py-1 text-center bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs font-semibold transition-colors">
                                    Confirm Transport Now
                                </button>
                            </form>
                        @endif
                    </div>
                @empty
                    <p class="text-xs text-slate-400 italic py-4 text-center">No transport services scheduled for tomorrow.</p>
                @endforelse
            </div>

            <!-- Tomorrow's Accommodation Check-ins/outs -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <span class="text-xs font-bold text-blue-800 uppercase tracking-wider flex items-center gap-1.5">
                        🏨 Tomorrow's Accommodation Feeds
                    </span>
                    <a href="{{ route('calendar.index') }}" class="text-[11px] font-semibold text-blue-600 hover:text-blue-700">Open Calendar →</a>
                </div>

                <!-- Tomorrow Check-ins -->
                <div>
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Arrivals ({{ $tomorrowCheckIns->count() }})</span>
                    @forelse($tomorrowCheckIns as $in)
                        <div class="p-2.5 mt-1.5 rounded-xl bg-blue-50/50 border border-blue-100 text-xs flex items-center justify-between">
                            <div>
                                <span class="font-bold text-slate-800">{{ $in->property->name }}</span>
                                <span class="text-slate-500 font-medium">({{ $in->room->name }})</span>
                                <div class="text-[11px] text-slate-500">{{ $in->booked_nights }} nights (Check-out: {{ $in->check_out_date->format('d M') }})</div>
                            </div>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $in->source_platform === 'airbnb' ? 'bg-rose-100 text-rose-700' : 'bg-blue-100 text-blue-700' }}">
                                {{ $in->source_platform }}
                            </span>
                        </div>
                    @empty
                        <p class="text-[11px] text-slate-400 italic py-1">No arrivals scheduled for tomorrow.</p>
                    @endforelse
                </div>

                <!-- Tomorrow Check-outs -->
                <div class="pt-2 border-t border-slate-100">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Departures ({{ $tomorrowCheckOuts->count() }})</span>
                    @forelse($tomorrowCheckOuts as $out)
                        <div class="p-2.5 mt-1.5 rounded-xl bg-amber-50/50 border border-amber-100 text-xs flex items-center justify-between">
                            <div>
                                <span class="font-bold text-slate-800">{{ $out->property->name }}</span>
                                <span class="text-slate-500 font-medium">({{ $out->room->name }})</span>
                                <div class="text-[11px] text-slate-500">Room available for clean & turnover</div>
                            </div>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $out->source_platform === 'airbnb' ? 'bg-rose-100 text-rose-700' : 'bg-blue-100 text-blue-700' }}">
                                {{ $out->source_platform }}
                            </span>
                        </div>
                    @empty
                        <p class="text-[11px] text-slate-400 italic py-1">No departures scheduled for tomorrow.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- 15.4 TODAY'S OPERATIONS BOARD -->
    <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div>
                <h2 class="text-base font-bold text-slate-900 tracking-tight">Today's Operations ({{ now()->format('d M Y') }})</h2>
                <p class="text-xs text-slate-500">Live tracker for current check-ins, safaris, and transport assignments.</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs text-slate-400">Total Activities: {{ $todaySafaris->count() + $todayTransports->count() }}</span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Safaris Today -->
            <div class="space-y-3">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Today's Safari Expeditions</h3>
                @forelse($todaySafaris as $ts)
                    <div class="p-3.5 rounded-xl border border-slate-200 flex items-center justify-between bg-slate-50/50">
                        <div>
                            <div class="font-bold text-sm text-slate-900">{{ $ts->primaryGuest->full_name }}</div>
                            <div class="text-xs text-slate-500">{{ $ts->pickup_time }} • {{ $ts->pickup_location }}</div>
                            <div class="text-xs text-slate-500">Driver: {{ $ts->driver_name ?? 'Unassigned' }} | Price: {{ number_format($ts->selling_price) }} LKR</div>
                        </div>
                        <div class="text-right space-y-1">
                            <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $ts->operational_status === 'completed' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                {{ $ts->operational_status }}
                            </span>
                            @if($ts->operational_status !== 'completed')
                                <form method="POST" action="{{ route('safaris.status', $ts->id) }}">
                                    @csrf
                                    <input type="hidden" name="status" value="completed">
                                    <button type="submit" class="block text-[11px] font-semibold text-emerald-600 hover:text-emerald-700">Mark Done ✓</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-slate-400 italic py-2">No safari activities scheduled for today.</p>
                @endforelse
            </div>

            <!-- Transports Today -->
            <div class="space-y-3">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Today's Transport Routes</h3>
                @forelse($todayTransports as $tt)
                    <div class="p-3.5 rounded-xl border border-slate-200 flex items-center justify-between bg-slate-50/50">
                        <div>
                            <div class="font-bold text-sm text-slate-900">{{ $tt->primaryGuest->full_name }}</div>
                            <div class="text-xs text-slate-500">{{ $tt->pickup_time }} • {{ $tt->pickup_location }} ➔ {{ $tt->drop_off_location }}</div>
                            <div class="text-xs text-slate-500">Driver: {{ $tt->driver_name ?? 'Unassigned' }} | Vehicle: {{ $tt->vehicle->registration_number ?? 'N/A' }}</div>
                        </div>
                        <div class="text-right space-y-1">
                            <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $tt->operational_status === 'completed' ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800' }}">
                                {{ str_replace('_', ' ', $tt->operational_status) }}
                            </span>
                            @if($tt->operational_status !== 'completed')
                                <form method="POST" action="{{ route('transports.status', $tt->id) }}">
                                    @csrf
                                    <input type="hidden" name="status" value="completed">
                                    <button type="submit" class="block text-[11px] font-semibold text-amber-600 hover:text-amber-700">Mark Done ✓</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-slate-400 italic py-2">No transport services scheduled for today.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- 15.2 VISUAL ANALYTICS & CHARTS (Chart.js) -->
    <div class="space-y-4">
        <h2 class="text-lg font-bold text-slate-900 tracking-tight">Financial & Operational Analytics</h2>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Chart 1: Monthly Revenue Trend -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">1. Monthly Revenue & Profit Trends (LKR)</h3>
                    <span class="text-[11px] text-slate-400">Past 6 Months</span>
                </div>
                <div class="h-64">
                    <canvas id="monthlyRevenueChart"></canvas>
                </div>
            </div>

            <!-- Chart 2: Revenue by Category -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">2. Revenue by Category (Safari vs Transport)</h3>
                    <span class="text-[11px] text-slate-400">{{ $finance['currency'] }}</span>
                </div>
                <div class="h-64 flex items-center justify-center">
                    <canvas id="categoryRevenueChart"></canvas>
                </div>
            </div>

            <!-- Chart 3: Revenue by Property -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">3. Revenue by Property Association</h3>
                    <span class="text-[11px] text-slate-400">Services Linked</span>
                </div>
                <div class="h-64">
                    <canvas id="propertyRevenueChart"></canvas>
                </div>
            </div>

            <!-- Chart 4: Revenue vs Cost Comparison -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">4. Revenue vs Operating Costs</h3>
                    <span class="text-[11px] text-slate-400">Margin Breakdown</span>
                </div>
                <div class="h-64">
                    <canvas id="revenueVsCostChart"></canvas>
                </div>
            </div>

            <!-- Chart 5 & 6: Safari & Transport Status Distribution -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">5. Safari Operational Status Distribution</h3>
                    <span class="text-[11px] text-slate-400">Lifecycle</span>
                </div>
                <div class="h-64 flex items-center justify-center">
                    <canvas id="safariStatusChart"></canvas>
                </div>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">6. Transport Operational Status Distribution</h3>
                    <span class="text-[11px] text-slate-400">Lifecycle</span>
                </div>
                <div class="h-64 flex items-center justify-center">
                    <canvas id="transportStatusChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.Chart === 'undefined') return;

    // 1. Monthly Revenue Trend Chart
    const monthlyCtx = document.getElementById('monthlyRevenueChart');
    if (monthlyCtx) {
        new window.Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: @json($monthlyTrends['labels']),
                datasets: [
                    {
                        label: 'Safari Revenue',
                        data: @json($monthlyTrends['safari_revenue']),
                        borderColor: '#059669',
                        backgroundColor: 'rgba(5, 150, 105, 0.1)',
                        fill: true,
                        tension: 0.3
                    },
                    {
                        label: 'Transport Revenue',
                        data: @json($monthlyTrends['transport_revenue']),
                        borderColor: '#D97706',
                        backgroundColor: 'rgba(217, 119, 6, 0.1)',
                        fill: true,
                        tension: 0.3
                    },
                    {
                        label: 'Net Profit',
                        data: @json($monthlyTrends['total_profits']),
                        borderColor: '#2563EB',
                        borderDash: [5, 5],
                        tension: 0.3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }

    // 2. Revenue by Category
    const catCtx = document.getElementById('categoryRevenueChart');
    if (catCtx) {
        new window.Chart(catCtx, {
            type: 'doughnut',
            data: {
                labels: ['Safari Expeditions', 'Transport & Transfers'],
                datasets: [{
                    data: [{{ $finance['safari']['revenue'] }}, {{ $finance['transport']['revenue'] }}],
                    backgroundColor: ['#059669', '#D97706']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }

    // 3. Revenue by Property
    const propCtx = document.getElementById('propertyRevenueChart');
    if (propCtx) {
        new window.Chart(propCtx, {
            type: 'bar',
            data: {
                labels: @json($revenueByPropertyLabels),
                datasets: [{
                    label: 'Revenue (LKR)',
                    data: @json($revenueByPropertyValues),
                    backgroundColor: ['#2563EB', '#9333EA', '#0D9488', '#64748B']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } }
            }
        });
    }

    // 4. Revenue vs Operating Costs
    const revCostCtx = document.getElementById('revenueVsCostChart');
    if (revCostCtx) {
        new window.Chart(revCostCtx, {
            type: 'bar',
            data: {
                labels: ['Safari', 'Transport', 'Total Portfolio'],
                datasets: [
                    {
                        label: 'Revenue',
                        data: [{{ $finance['safari']['revenue'] }}, {{ $finance['transport']['revenue'] }}, {{ $finance['total_revenue'] }}],
                        backgroundColor: '#10B981'
                    },
                    {
                        label: 'Operating Cost',
                        data: [{{ $finance['safari']['cost'] }}, {{ $finance['transport']['cost'] }}, {{ $finance['total_cost'] }}],
                        backgroundColor: '#EF4444'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }

    // 5. Safari Status Chart
    const safStatusCtx = document.getElementById('safariStatusChart');
    if (safStatusCtx) {
        new window.Chart(safStatusCtx, {
            type: 'pie',
            data: {
                labels: @json(array_keys($safariStatusCounts)),
                datasets: [{
                    data: @json(array_values($safariStatusCounts)),
                    backgroundColor: ['#F59E0B', '#3B82F6', '#10B981', '#059669', '#EF4444']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }

    // 6. Transport Status Chart
    const trnStatusCtx = document.getElementById('transportStatusChart');
    if (trnStatusCtx) {
        new window.Chart(trnStatusCtx, {
            type: 'pie',
            data: {
                labels: @json(array_keys($transportStatusCounts)),
                datasets: [{
                    data: @json(array_values($transportStatusCounts)),
                    backgroundColor: ['#F59E0B', '#3B82F6', '#8B5CF6', '#10B981', '#059669', '#EF4444']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }
});
</script>
@endpush
@endsection
