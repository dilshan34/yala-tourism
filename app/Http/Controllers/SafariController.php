<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Driver;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\Property;
use App\Models\SafariBooking;
use App\Models\SafariOperator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SafariController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');
        $dateFilter = $request->query('date_filter');
        $propertyId = $request->query('property_id');
        $search = $request->query('search');

        $query = SafariBooking::with(['primaryGuest', 'property', 'operator', 'driver'])
            ->latest('safari_date');

        if ($status && $status !== 'all') {
            $query->where('operational_status', $status);
        }

        if ($propertyId && $propertyId !== 'all') {
            if ($propertyId === 'external') {
                $query->whereNull('property_id');
            } else {
                $query->where('property_id', $propertyId);
            }
        }

        if ($dateFilter === 'today') {
            $query->whereDate('safari_date', today());
        } elseif ($dateFilter === 'tomorrow') {
            $query->whereDate('safari_date', today()->addDay());
        } elseif ($dateFilter === 'this_month') {
            $query->whereBetween('safari_date', [today()->startOfMonth(), today()->endOfMonth()]);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('reference_no', 'like', "%{$search}%")
                  ->orWhere('pickup_location', 'like', "%{$search}%")
                  ->orWhere('driver_name', 'like', "%{$search}%")
                  ->orWhereHas('primaryGuest', function ($gQ) use ($search) {
                      $gQ->where('full_name', 'like', "%{$search}%")
                         ->orWhere('phone_number', 'like', "%{$search}%");
                  });
            });
        }

        $safaris = $query->paginate(15);
        $properties = Property::active()->get();

        return view('safaris.index', compact('safaris', 'properties', 'status', 'dateFilter', 'propertyId', 'search'));
    }

    public function create()
    {
        $properties = Property::active()->get();
        $guests = Guest::latest()->get();
        $operators = SafariOperator::where('is_active', true)->get();
        $drivers = Driver::where('is_active', true)->get();

        // Auto-generate next safari reference number
        $refPrefix = 'SAF-' . date('Ym') . '-';
        $latest = SafariBooking::where('reference_no', 'like', "{$refPrefix}%")->latest('id')->first();
        $nextSeq = $latest ? ((int) substr($latest->reference_no, -4) + 1) : 1;
        $suggestedRef = $refPrefix . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);

        return view('safaris.create', compact('properties', 'guests', 'operators', 'drivers', 'suggestedRef'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'reference_no' => ['required', 'string', 'unique:safari_bookings,reference_no'],
            'guest_selection' => ['required', 'string'], // 'existing' or 'new'
            'existing_guest_id' => ['required_if:guest_selection,existing', 'nullable', 'exists:guests,id'],
            'new_guest_name' => ['required_if:guest_selection,new', 'nullable', 'string', 'max:191'],
            'new_guest_phone' => ['required_if:guest_selection,new', 'nullable', 'string', 'max:50'],
            'new_guest_country' => ['required_if:guest_selection,new', 'nullable', 'string', 'max:100'],
            'new_guest_email' => ['nullable', 'email'],
            'property_choice' => ['required', 'string'], // 'property_id', 'other', 'external'
            'property_id' => ['nullable', 'exists:properties,id'],
            'custom_property_name' => ['nullable', 'string', 'max:191'],
            'safari_date' => ['required', 'date'],
            'safari_type' => ['required', 'in:half_day_morning,full_day,evening'],
            'safari_category' => ['required', 'in:private,shared'],
            'guest_count' => ['required', 'integer', 'min:1'],
            'pickup_time' => ['required'],
            'pickup_location' => ['required', 'string', 'max:255'],
            'national_park_block' => ['nullable', 'string', 'max:100'],
            'safari_operator_id' => ['nullable', 'exists:safari_operators,id'],
            'driver_id' => ['nullable', 'exists:drivers,id'],
            'driver_name' => ['nullable', 'string', 'max:191'],
            'driver_phone' => ['nullable', 'string', 'max:50'],
            'jeep_details' => ['nullable', 'string', 'max:191'],
            'shared_jeep_group_id' => ['nullable', 'string', 'max:50'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'operator_cost' => ['nullable', 'numeric', 'min:0'],
            'initial_payment_amount' => ['nullable', 'numeric', 'min:0'],
            'initial_payment_method' => ['nullable', 'in:cash,bank_transfer,card,other'],
            'notes' => ['nullable', 'string'],
        ]);

        // Resolve or create primary guest
        if ($validated['guest_selection'] === 'new') {
            $guest = Guest::create([
                'full_name' => $validated['new_guest_name'],
                'phone_number' => $validated['new_guest_phone'],
                'country' => $validated['new_guest_country'] ?? 'International',
                'email' => $validated['new_guest_email'] ?? null,
            ]);
        } else {
            $guest = Guest::findOrFail($validated['existing_guest_id']);
        }

        // Resolve property
        $propId = null;
        $customPropName = null;
        if ($validated['property_choice'] === 'other') {
            $customPropName = $validated['custom_property_name'] ?? 'Other Property';
            // Also create a permanent record in properties table if specified
            $createdProp = Property::create([
                'name' => $customPropName,
                'slug' => Str::slug($customPropName) . '-' . rand(100, 999),
                'is_predefined' => false,
                'is_active' => true,
            ]);
            $propId = $createdProp->id;
        } elseif ($validated['property_choice'] === 'external') {
            $propId = null;
            $customPropName = 'External / No Accommodation';
        } else {
            $propId = $validated['property_id'];
        }

        // Driver details fallback
        $driverName = $validated['driver_name'];
        $driverPhone = $validated['driver_phone'];
        if (!empty($validated['driver_id'])) {
            $driverObj = Driver::find($validated['driver_id']);
            if ($driverObj) {
                $driverName = $driverName ?: $driverObj->name;
                $driverPhone = $driverPhone ?: $driverObj->phone_number;
            }
        }

        $safari = SafariBooking::create([
            'reference_no' => $validated['reference_no'],
            'primary_guest_id' => $guest->id,
            'property_id' => $propId,
            'custom_property_name' => $customPropName,
            'safari_date' => $validated['safari_date'],
            'safari_type' => $validated['safari_type'],
            'safari_category' => $validated['safari_category'],
            'guest_count' => $validated['guest_count'],
            'pickup_time' => $validated['pickup_time'],
            'pickup_location' => $validated['pickup_location'],
            'national_park_block' => $validated['national_park_block'] ?? 'Yala Block 1',
            'safari_operator_id' => $validated['safari_operator_id'] ?? null,
            'driver_id' => $validated['driver_id'] ?? null,
            'driver_name' => $driverName,
            'driver_phone' => $driverPhone,
            'jeep_details' => $validated['jeep_details'] ?? null,
            'shared_jeep_group_id' => $validated['shared_jeep_group_id'] ?? null,
            'selling_price' => $validated['selling_price'],
            'operator_cost' => $validated['operator_cost'] !== null && $validated['operator_cost'] !== '' ? $validated['operator_cost'] : null,
            'amount_paid' => 0.00,
            'operational_status' => 'pending',
            'notes' => $validated['notes'] ?? null,
        ]);

        // Attach primary guest to participants list
        $safari->participants()->attach($guest->id, ['is_primary' => true]);

        // Record initial payment if provided
        $initialPay = (float) ($validated['initial_payment_amount'] ?? 0);
        if ($initialPay > 0) {
            Payment::create([
                'payable_type' => SafariBooking::class,
                'payable_id' => $safari->id,
                'amount' => $initialPay,
                'currency' => 'LKR',
                'payment_date' => now()->toDateString(),
                'payment_method' => $validated['initial_payment_method'] ?? 'cash',
                'transaction_reference' => 'INITIAL-PAY',
                'recorded_by_user_id' => auth()->id(),
            ]);

            $safari->syncPaymentsLedger();
        }

        ActivityLog::log('created', $safari, "Safari {$safari->reference_no} created for guest {$guest->full_name}");

        return redirect()->route('safaris.show', $safari->id)
            ->with('success', "Safari booking {$safari->reference_no} created successfully.");
    }

    public function show(SafariBooking $safari)
    {
        $safari->load(['primaryGuest', 'property', 'operator', 'driver', 'participants', 'payments.recordedBy']);
        $drivers = Driver::where('is_active', true)->get();
        $operators = SafariOperator::where('is_active', true)->get();

        return view('safaris.show', compact('safari', 'drivers', 'operators'));
    }

    public function edit(SafariBooking $safari)
    {
        $properties = Property::active()->get();
        $guests = Guest::all();
        $operators = SafariOperator::where('is_active', true)->get();
        $drivers = Driver::where('is_active', true)->get();

        return view('safaris.edit', compact('safari', 'properties', 'guests', 'operators', 'drivers'));
    }

    public function update(Request $request, SafariBooking $safari)
    {
        $validated = $request->validate([
            'safari_date' => ['required', 'date'],
            'safari_type' => ['required', 'in:half_day_morning,full_day,evening'],
            'safari_category' => ['required', 'in:private,shared'],
            'guest_count' => ['required', 'integer', 'min:1'],
            'pickup_time' => ['required'],
            'pickup_location' => ['required', 'string', 'max:255'],
            'national_park_block' => ['nullable', 'string', 'max:100'],
            'safari_operator_id' => ['nullable', 'exists:safari_operators,id'],
            'driver_id' => ['nullable', 'exists:drivers,id'],
            'driver_name' => ['nullable', 'string', 'max:191'],
            'driver_phone' => ['nullable', 'string', 'max:50'],
            'jeep_details' => ['nullable', 'string', 'max:191'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'operator_cost' => ['nullable', 'numeric', 'min:0'],
            'operational_status' => ['required', 'in:pending,confirmed,in_progress,completed,cancelled'],
            'notes' => ['nullable', 'string'],
        ]);

        $safari->update([
            'safari_date' => $validated['safari_date'],
            'safari_type' => $validated['safari_type'],
            'safari_category' => $validated['safari_category'],
            'guest_count' => $validated['guest_count'],
            'pickup_time' => $validated['pickup_time'],
            'pickup_location' => $validated['pickup_location'],
            'national_park_block' => $validated['national_park_block'] ?? 'Yala Block 1',
            'safari_operator_id' => $validated['safari_operator_id'] ?? null,
            'driver_id' => $validated['driver_id'] ?? null,
            'driver_name' => $validated['driver_name'],
            'driver_phone' => $validated['driver_phone'],
            'jeep_details' => $validated['jeep_details'],
            'selling_price' => $validated['selling_price'],
            'operator_cost' => $validated['operator_cost'] !== null && $validated['operator_cost'] !== '' ? $validated['operator_cost'] : null,
            'operational_status' => $validated['operational_status'],
            'notes' => $validated['notes'],
        ]);

        ActivityLog::log('updated', $safari, "Safari {$safari->reference_no} details updated");

        return redirect()->route('safaris.show', $safari->id)->with('success', 'Safari updated successfully.');
    }

    /**
     * One-click Status Transitions
     */
    public function updateStatus(Request $request, SafariBooking $safari)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,confirmed,in_progress,completed,cancelled'],
        ]);

        $prev = $safari->operational_status;
        $new = $validated['status'];

        $updateData = ['operational_status' => $new];
        if ($new === 'completed') {
            $updateData['completed_at'] = now();
            $updateData['completed_by_user_id'] = auth()->id();
        }

        $safari->update($updateData);

        ActivityLog::log('status_change', $safari, "Safari {$safari->reference_no} status changed from {$prev} to {$new}");

        return back()->with('success', "Safari status updated to " . ucfirst(str_replace('_', ' ', $new)));
    }

    /**
     * Add payment to safari ledger
     */
    public function addPayment(Request $request, SafariBooking $safari)
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['required', 'in:cash,bank_transfer,card,other'],
            'transaction_reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        Payment::create([
            'payable_type' => SafariBooking::class,
            'payable_id' => $safari->id,
            'amount' => $validated['amount'],
            'currency' => 'LKR',
            'payment_date' => $validated['payment_date'],
            'payment_method' => $validated['payment_method'],
            'transaction_reference' => $validated['transaction_reference'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'recorded_by_user_id' => auth()->id(),
        ]);

        $safari->syncPaymentsLedger();

        ActivityLog::log('payment_added', $safari, "Recorded payment of {$validated['amount']} LKR for Safari {$safari->reference_no}");

        return back()->with('success', "Payment of " . number_format($validated['amount'], 2) . " LKR recorded successfully.");
    }
}
