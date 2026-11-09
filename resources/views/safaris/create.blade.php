@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="safariCreateForm()">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">Create Safari Expedition Booking</h1>
            <p class="text-xs text-slate-500 mt-0.5">Independent wildlife safari reservation, vehicle arrangement, and financial tracking.</p>
        </div>
        <a href="{{ route('safaris.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800">
            ← Back to Safaris
        </a>
    </div>

    @if ($errors->any())
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs">
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('safaris.store') }}" class="bg-white p-8 rounded-3xl border border-slate-200/80 shadow-xs space-y-8">
        @csrf

        <!-- Section 1: Reference & Guest Information -->
        <div class="space-y-4">
            <div class="border-b border-slate-100 pb-2">
                <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">1. Reference & Primary Guest</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Reference Number *</label>
                    <input type="text" name="reference_no" value="{{ old('reference_no', $suggestedRef) }}" required
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 font-mono text-sm" />
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Guest Origin Mode *</label>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="p-2 border rounded-xl text-center cursor-pointer text-xs font-semibold"
                               :class="guestSelection === 'new' ? 'border-emerald-600 bg-emerald-50 text-emerald-800' : 'border-slate-200'">
                            <input type="radio" name="guest_selection" value="new" x-model="guestSelection" class="sr-only" />
                            + New Guest
                        </label>
                        <label class="p-2 border rounded-xl text-center cursor-pointer text-xs font-semibold"
                               :class="guestSelection === 'existing' ? 'border-emerald-600 bg-emerald-50 text-emerald-800' : 'border-slate-200'">
                            <input type="radio" name="guest_selection" value="existing" x-model="guestSelection" class="sr-only" />
                            Existing Directory
                        </label>
                    </div>
                </div>
            </div>

            <!-- New Guest Inputs -->
            <div x-show="guestSelection === 'new'" class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-3">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Guest Full Name *</label>
                        <input type="text" name="new_guest_name" value="{{ old('new_guest_name') }}" placeholder="e.g. John Doe" class="w-full px-3 py-2 border rounded-xl text-xs bg-white" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Phone / WhatsApp *</label>
                        <input type="text" name="new_guest_phone" value="{{ old('new_guest_phone') }}" placeholder="+94 77 123 4567" class="w-full px-3 py-2 border rounded-xl text-xs bg-white" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Country / Nationality *</label>
                        <input type="text" name="new_guest_country" value="{{ old('new_guest_country', 'International') }}" class="w-full px-3 py-2 border rounded-xl text-xs bg-white" />
                    </div>
                </div>
            </div>

            <!-- Existing Guest Selector -->
            <div x-show="guestSelection === 'existing'" class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80">
                <label class="block text-xs font-semibold text-slate-700 mb-1">Select Registered Guest *</label>
                <select name="existing_guest_id" class="w-full px-3 py-2 border rounded-xl text-xs bg-white">
                    <option value="">Choose guest from directory...</option>
                    @foreach($guests as $g)
                        <option value="{{ $g->id }}">{{ $g->full_name }} ({{ $g->phone_number }} - {{ $g->country }})</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Section 2: Property Association -->
        <div class="space-y-4">
            <div class="border-b border-slate-100 pb-2">
                <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">2. Accommodation Property Association</h2>
                <p class="text-xs text-slate-500">Safaris may optionally be associated with a property or created for external clients.</p>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                @foreach($properties as $prop)
                    <label class="border p-3 rounded-2xl cursor-pointer text-center space-y-1 transition-all"
                           :class="propertyChoice === 'prop_{{ $prop->id }}' ? 'border-blue-600 bg-blue-50/50 text-blue-900 ring-2 ring-blue-500/20' : 'border-slate-200 hover:border-slate-300'">
                        <input type="radio" name="property_choice" value="prop_{{ $prop->id }}" x-model="propertyChoice" class="sr-only" />
                        <span class="w-2.5 h-2.5 mx-auto rounded-full block" style="background-color: {{ $prop->color_hex ?? '#2563EB' }}"></span>
                        <span class="text-xs font-bold block">{{ $prop->name }}</span>
                    </label>
                @endforeach

                <label class="border p-3 rounded-2xl cursor-pointer text-center space-y-1 transition-all"
                       :class="propertyChoice === 'other' ? 'border-teal-600 bg-teal-50/50 text-teal-900 ring-2 ring-teal-500/20' : 'border-slate-200 hover:border-slate-300'">
                    <input type="radio" name="property_choice" value="other" x-model="propertyChoice" class="sr-only" />
                    <span class="w-2.5 h-2.5 mx-auto rounded-full bg-teal-600 block"></span>
                    <span class="text-xs font-bold block">Other Property</span>
                </label>

                <label class="border p-3 rounded-2xl cursor-pointer text-center space-y-1 transition-all"
                       :class="propertyChoice === 'external' ? 'border-slate-600 bg-slate-100 text-slate-900 ring-2 ring-slate-400' : 'border-slate-200 hover:border-slate-300'">
                    <input type="radio" name="property_choice" value="external" x-model="propertyChoice" class="sr-only" />
                    <span class="w-2.5 h-2.5 mx-auto rounded-full bg-slate-400 block"></span>
                    <span class="text-xs font-bold block">External / None</span>
                </label>
            </div>

            <!-- Hidden field for property_id mapped from selection -->
            <input type="hidden" name="property_id" :value="resolvedPropertyId" />

            <!-- Custom property input if 'Other' selected -->
            <div x-show="propertyChoice === 'other'" class="p-3.5 rounded-2xl bg-teal-50/60 border border-teal-200 space-y-1">
                <label class="block text-xs font-bold text-teal-900">Custom Property Name *</label>
                <input type="text" name="custom_property_name" placeholder="Enter guest house / villa name..." class="w-full px-3 py-2 border rounded-xl text-xs bg-white" />
            </div>
        </div>

        <!-- Section 3: Safari Operational Parameters -->
        <div class="space-y-4">
            <div class="border-b border-slate-100 pb-2">
                <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">3. Safari Schedule & Operations</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Safari Date *</label>
                    <input type="date" name="safari_date" value="{{ old('safari_date', now()->addDay()->toDateString()) }}" required class="w-full px-3 py-2 border rounded-xl text-xs" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Pickup Time *</label>
                    <input type="time" name="pickup_time" value="{{ old('pickup_time', '05:30') }}" required class="w-full px-3 py-2 border rounded-xl text-xs" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Guest Count (Pax) *</label>
                    <input type="number" name="guest_count" value="{{ old('guest_count', 2) }}" min="1" required class="w-full px-3 py-2 border rounded-xl text-xs" />
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Safari Type *</label>
                    <select name="safari_type" required class="w-full px-3 py-2 border rounded-xl text-xs">
                        <option value="half_day_morning">Half-Day Morning Safari</option>
                        <option value="full_day">Full-Day Safari</option>
                        <option value="evening">Evening Safari</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Safari Category *</label>
                    <select name="safari_category" x-model="safariCategory" required class="w-full px-3 py-2 border rounded-xl text-xs">
                        <option value="private">Private Safari</option>
                        <option value="shared">Shared Safari</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">National Park Block *</label>
                    <input type="text" name="national_park_block" value="{{ old('national_park_block', 'Yala Block 1') }}" class="w-full px-3 py-2 border rounded-xl text-xs" />
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Pickup Location / Meeting Point *</label>
                <input type="text" name="pickup_location" value="{{ old('pickup_location') }}" placeholder="e.g. Wild Pulse Lobby, Palatupana junction" required class="w-full px-3 py-2 border rounded-xl text-xs" />
            </div>

            <!-- Shared Jeep Identifier if category is shared -->
            <div x-show="safariCategory === 'shared'" class="p-3.5 rounded-2xl bg-amber-50/60 border border-amber-200">
                <label class="block text-xs font-bold text-amber-900 mb-1">Shared Jeep Group ID / Vehicle Code</label>
                <input type="text" name="shared_jeep_group_id" placeholder="e.g. JEEP-SHARED-A" class="w-full px-3 py-2 border rounded-xl text-xs bg-white" />
                <p class="text-[11px] text-amber-700 mt-1">Assign the same group code to link multiple guest parties into one shared vehicle.</p>
            </div>
        </div>

        <!-- Section 4: Operator & Driver Assignment -->
        <div class="space-y-4">
            <div class="border-b border-slate-100 pb-2">
                <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">4. Operator, Driver & Jeep Details</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Safari Operator</label>
                    <select name="safari_operator_id" class="w-full px-3 py-2 border rounded-xl text-xs">
                        <option value="">Select Operator (Optional)...</option>
                        @foreach($operators as $op)
                            <option value="{{ $op->id }}">{{ $op->name }} ({{ $op->phone }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Assign Registered Driver</label>
                    <select name="driver_id" class="w-full px-3 py-2 border rounded-xl text-xs">
                        <option value="">Select Driver (Optional)...</option>
                        @foreach($drivers as $d)
                            <option value="{{ $d->id }}">{{ $d->name }} ({{ $d->phone_number }})</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Driver Name (Manual / Override)</label>
                    <input type="text" name="driver_name" value="{{ old('driver_name') }}" placeholder="e.g. Kamal Perera" class="w-full px-3 py-2 border rounded-xl text-xs" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Driver Phone Number</label>
                    <input type="text" name="driver_phone" value="{{ old('driver_phone') }}" placeholder="+94 77 123 4567" class="w-full px-3 py-2 border rounded-xl text-xs" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Jeep Details / Registration</label>
                    <input type="text" name="jeep_details" value="{{ old('jeep_details') }}" placeholder="e.g. Land Cruiser WP-CA-8899" class="w-full px-3 py-2 border rounded-xl text-xs" />
                </div>
            </div>
        </div>

        <!-- Section 5: Financials & Profit Ledger -->
        <div class="space-y-4">
            <div class="border-b border-slate-100 pb-2">
                <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">5. Financial Ledger & Profit Calculation (LKR)</h2>
                <p class="text-xs text-slate-500">Profit is calculated as Selling Price - Operator Cost. If cost is blank, it is explicitly flagged as unrecorded.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Selling Price (LKR) *</label>
                    <input type="number" step="0.01" name="selling_price" x-model.number="sellingPrice" required class="w-full px-3 py-2 border rounded-xl text-xs font-bold" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Operator Cost (LKR)</label>
                    <input type="number" step="0.01" name="operator_cost" x-model.number="operatorCost" placeholder="Leave empty if unrecorded" class="w-full px-3 py-2 border rounded-xl text-xs" />
                </div>
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                    <span class="text-[11px] font-semibold text-slate-500 block uppercase">Calculated Profit</span>
                    <div class="text-lg font-bold" :class="profitValue !== null ? 'text-emerald-600' : 'text-slate-400'">
                        <span x-text="profitValue !== null ? formatCurrency(profitValue) : 'Unrecorded'"></span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Initial Advance Payment Received (Optional)</label>
                    <input type="number" step="0.01" name="initial_payment_amount" value="{{ old('initial_payment_amount', 0) }}" class="w-full px-3 py-2 border rounded-xl text-xs" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Payment Method</label>
                    <select name="initial_payment_method" class="w-full px-3 py-2 border rounded-xl text-xs">
                        <option value="cash">Cash</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="card">Credit / Debit Card</option>
                        <option value="other">Other</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Special Notes / Dietary Requirements</label>
                <textarea name="notes" rows="2" placeholder="e.g. Breakfast packs needed, guest interested in leopard tracking..." class="w-full px-3 py-2 border rounded-xl text-xs"></textarea>
            </div>
        </div>

        <div class="pt-6 border-t border-slate-100 flex items-center justify-end gap-3">
            <a href="{{ route('safaris.index') }}" class="px-4 py-2 text-xs font-semibold text-slate-500">Cancel</a>
            <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-xs transition-colors">
                Confirm & Create Safari Booking
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function safariCreateForm() {
    return {
        guestSelection: 'new',
        propertyChoice: 'prop_{{ $properties->first()->id ?? 1 }}',
        safariCategory: 'private',
        sellingPrice: 45000,
        operatorCost: 28000,

        get resolvedPropertyId() {
            if (this.propertyChoice.startsWith('prop_')) {
                return this.propertyChoice.replace('prop_', '');
            }
            return '';
        },

        get profitValue() {
            if (this.operatorCost !== null && this.operatorCost !== '' && !isNaN(this.operatorCost)) {
                return (this.sellingPrice || 0) - this.operatorCost;
            }
            return null;
        },

        formatCurrency(num) {
            return new Intl.NumberFormat('en-LK', { style: 'currency', currency: 'LKR' }).format(num);
        }
    }
}
</script>
@endpush
@endsection
