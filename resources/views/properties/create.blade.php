@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto space-y-6" x-data="{ choice: 'other' }">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">Add New Property</h1>
            <p class="text-xs text-slate-500 mt-0.5">Select a predefined property or register a brand-new custom property record.</p>
        </div>
        <a href="{{ route('properties.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800">
            ← Back to Properties
        </a>
    </div>

    <div class="bg-white p-8 rounded-3xl border border-slate-200/80 shadow-xs">
        <form method="POST" action="{{ route('properties.store') }}" class="space-y-6">
            @csrf

            <!-- Property Choice Selection -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Property Type Selection</label>
                <div class="grid grid-cols-3 gap-3">
                    <label class="border p-3.5 rounded-2xl cursor-pointer transition-all flex flex-col items-center text-center space-y-1.5"
                           :class="choice === 'wild_pulse' ? 'border-blue-600 bg-blue-50/50 text-blue-900 ring-2 ring-blue-500/20' : 'border-slate-200 hover:border-slate-300'">
                        <input type="radio" name="property_choice" value="wild_pulse" x-model="choice" class="sr-only" />
                        <span class="w-3 h-3 rounded-full bg-blue-600"></span>
                        <span class="text-xs font-bold">Wild Pulse</span>
                        <span class="text-[10px] text-slate-500">Predefined</span>
                    </label>

                    <label class="border p-3.5 rounded-2xl cursor-pointer transition-all flex flex-col items-center text-center space-y-1.5"
                           :class="choice === 'yala_bliss' ? 'border-purple-600 bg-purple-50/50 text-purple-900 ring-2 ring-purple-500/20' : 'border-slate-200 hover:border-slate-300'">
                        <input type="radio" name="property_choice" value="yala_bliss" x-model="choice" class="sr-only" />
                        <span class="w-3 h-3 rounded-full bg-purple-600"></span>
                        <span class="text-xs font-bold">Yala Bliss</span>
                        <span class="text-[10px] text-slate-500">Predefined</span>
                    </label>

                    <label class="border p-3.5 rounded-2xl cursor-pointer transition-all flex flex-col items-center text-center space-y-1.5"
                           :class="choice === 'other' ? 'border-emerald-600 bg-emerald-50/50 text-emerald-900 ring-2 ring-emerald-500/20' : 'border-slate-200 hover:border-slate-300'">
                        <input type="radio" name="property_choice" value="other" x-model="choice" class="sr-only" />
                        <span class="w-3 h-3 rounded-full bg-emerald-600"></span>
                        <span class="text-xs font-bold">Other</span>
                        <span class="text-[10px] text-slate-500">Custom Property</span>
                    </label>
                </div>
            </div>

            <!-- Custom Property Name Field (Shown when 'Other' selected) -->
            <div x-show="choice === 'other'" x-cloak class="p-4 rounded-2xl bg-emerald-50/40 border border-emerald-200/80 space-y-2">
                <label for="custom_name" class="block text-xs font-bold text-emerald-900 uppercase tracking-wider">
                    Custom Property Name *
                </label>
                <input id="custom_name" type="text" name="custom_name" value="{{ old('custom_name') }}" placeholder="e.g. Green Garden Guest House, Leopard Rest Villa"
                       class="w-full px-3.5 py-2.5 rounded-xl border border-emerald-300 bg-white text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/20" />
                <p class="text-[11px] text-emerald-700">This property will be permanently saved to your database and can have multiple rooms and iCal connections.</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="code" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Property Short Code</label>
                    <input id="code" type="text" name="code" value="{{ old('code') }}" placeholder="e.g. GGH, LRV"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/20" />
                </div>
                <div>
                    <label for="color_hex" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Calendar Badge Color</label>
                    <input id="color_hex" type="color" name="color_hex" value="#0D9488"
                           class="w-full h-10 p-1 rounded-xl border border-slate-300 cursor-pointer" />
                </div>
            </div>

            <div>
                <label for="address" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Address / Region</label>
                <input id="address" type="text" name="address" value="{{ old('address') }}" placeholder="e.g. Kirinda Road, Tissamaharama"
                       class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/20" />
            </div>

            <div>
                <label for="description" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Description (Optional)</label>
                <textarea id="description" name="description" rows="3" placeholder="Notes on property amenities, surroundings..."
                          class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/20">{{ old('description') }}</textarea>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <a href="{{ route('properties.index') }}" class="px-4 py-2 text-xs font-semibold text-slate-500 hover:text-slate-800">Cancel</a>
                <button type="submit" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-xs transition-colors">
                    Save Property Record
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
