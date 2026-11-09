<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Driver;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\Property;
use App\Models\TransportBooking;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TransportController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');
        $dateFilter = $request->query('date_filter');
        $propertyId = $request->query('property_id');
        $search = $request->query('search');

        $query = TransportBooking::with(['primaryGuest', 'property', 'driver', 'vehicle'])
            ->latest('transport_date');

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
            $query->whereDate('transport_date', today());
        } elseif ($dateFilter === 'tomorrow') {
            $query->whereDate('transport_date', today()->addDay());
        } elseif ($dateFilter === 'this_month') {
            $query->whereBetween('transport_date', [today()->startOfMonth(), today()->endOfMonth()]);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('reference_no', 'like', "%{$search}%")
                  ->orWhere('pickup_location', 'like', "%{$search}%")
                  ->orWhere('drop_off_location', 'like', "%{$search}%")
                  ->orWhere('driver_name', 'like', "%{$search}%")
                  ->orWhereHas('primaryGuest', function ($gQ) use ($search) {
                      $gQ->where('full_name', 'like', "%{$search}%")
                         ->orWhere('phone_number', 'like', "%{$search}%");
                  });
            });
        }

        $transports = $query->paginate(15);
        $properties = Property::active()->get();

        return view('transports.index', compact('transports', 'properties', 'status', 'dateFilter', 'propertyId', 'search'));
    }

    public function create()
    {
        $properties = Property::active()->get();
        $guests = Guest::latest()->get();
        $drivers = Driver::where('is_active', true)->get();
        $vehicles = Vehicle::where('is_active', true)->get();

        // Auto-generate reference number
        $refPrefix = 'TRN-' . date('Ym') . '-';
        $latest = TransportBooking::where('reference_no', 'like', "{$refPrefix}%")->latest('id')->first();
        $nextSeq = $latest ? ((int) substr($latest->reference_no, -4) + 1) : 1;
        $suggestedRef = $refPrefix . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);

        return view('transports.create', compact('properties', 'guests', 'drivers', 'vehicles', 'suggestedRef'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'reference_no' => ['required', 'string', 'unique:transport_bookings,reference_no'],
            'guest_selection' => ['required', 'string'], // 'existing' or 'new'
            'existing_guest_id' => ['required_if:guest_selection,existing', 'nullable', 'exists:guests,id'],
            'new_guest_name' => ['required_if:guest_selection,new', 'nullable', 'string', 'max:191'],
            'new_guest_phone' => ['required_if:guest_selection,new', 'nullable', 'string', 'max:50'],
            'new_guest_country' => ['required_if:guest_selection,new', 'nullable', 'string', 'max:100'],
            'new_guest_email' => ['nullable', 'email'],
            'property_choice' => ['required', 'string'],
            'property_id' => ['nullable', 'exists:properties,id'],
            'custom_property_name' => ['nullable', 'string', 'max:191'],
            'transport_date' => ['required', 'date'],
            'pickup_time' => ['required'],
            'pickup_location' => ['required', 'string', 'max:255'],
            'drop_off_location' => ['required', 'string', 'max:255'],
            'additional_stops' => ['nullable', 'string'],
            'transport_type' => ['required', 'in:airport_pickup,airport_drop,hotel_transfer,intercity,day_tour,other'],
            'driver_id' => ['nullable', 'exists:drivers,id'],
            'vehicle_id' => ['nullable', 'exists:vehicles,id'],
            'driver_name' => ['nullable', 'string', 'max:191'],
            'driver_phone' => ['nullable', 'string', 'max:50'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'driver_cost' => ['nullable', 'numeric', 'min:0'],
            'initial_payment_amount' => ['nullable', 'numeric', 'min:0'],
            'initial_payment_method' => ['nullable', 'in:cash,bank_transfer,card,other'],
            'notes' => ['nullable', 'string'],
        ]);

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

        $propId = null;
        $customPropName = null;
        if ($validated['property_choice'] === 'other') {
            $customPropName = $validated['custom_property_name'] ?? 'Other Property';
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

        $driverName = $validated['driver_name'];
        $driverPhone = $validated['driver_phone'];
        if (!empty($validated['driver_id'])) {
            $driverObj = Driver::find($validated['driver_id']);
            if ($driverObj) {
                $driverName = $driverName ?: $driverObj->name;
                $driverPhone = $driverPhone ?: $driverObj->phone_number;
            }
        }

        $transport = TransportBooking::create([
            'reference_no' => $validated['reference_no'],
            'primary_guest_id' => $guest->id,
            'property_id' => $propId,
            'custom_property_name' => $customPropName,
            'transport_date' => $validated['transport_date'],
            'pickup_time' => $validated['pickup_time'],
            'pickup_location' => $validated['pickup_location'],
            'drop_off_location' => $validated['drop_off_location'],
            'additional_stops' => $validated['additional_stops'] ?? null,
            'transport_type' => $validated['transport_type'],
            'driver_id' => $validated['driver_id'] ?? null,
            'vehicle_id' => $validated['vehicle_id'] ?? null,
            'driver_name' => $driverName,
            'driver_phone' => $driverPhone,
            'selling_price' => $validated['selling_price'],
            'driver_cost' => $validated['driver_cost'] !== null && $validated['driver_cost'] !== '' ? $validated['driver_cost'] : null,
            'amount_paid' => 0.00,
            'operational_status' => !empty($validated['driver_id']) ? 'driver_assigned' : 'pending',
            'notes' => $validated['notes'] ?? null,
        ]);

        $transport->participants()->attach($guest->id, ['is_primary' => true]);

        $initialPay = (float) ($validated['initial_payment_amount'] ?? 0);
        if ($initialPay > 0) {
            Payment::create([
                'payable_type' => TransportBooking::class,
                'payable_id' => $transport->id,
                'amount' => $initialPay,
                'currency' => 'LKR',
                'payment_date' => now()->toDateString(),
                'payment_method' => $validated['initial_payment_method'] ?? 'cash',
                'transaction_reference' => 'INITIAL-PAY',
                'recorded_by_user_id' => auth()->id(),
            ]);

            $transport->syncPaymentsLedger();
        }

        ActivityLog::log('created', $transport, "Transport {$transport->reference_no} created for guest {$guest->full_name}");

        return redirect()->route('transports.show', $transport->id)
            ->with('success', "Transport booking {$transport->reference_no} created successfully.");
    }

    public function show(TransportBooking $transport)
    {
        $transport->load(['primaryGuest', 'property', 'driver', 'vehicle', 'participants', 'payments.recordedBy']);
        $drivers = Driver::where('is_active', true)->get();
        $vehicles = Vehicle::where('is_active', true)->get();

        return view('transports.show', compact('transport', 'drivers', 'vehicles'));
    }

    public function edit(TransportBooking $transport)
    {
        $properties = Property::active()->get();
        $guests = Guest::all();
        $drivers = Driver::where('is_active', true)->get();
        $vehicles = Vehicle::where('is_active', true)->get();

        return view('transports.edit', compact('transport', 'properties', 'guests', 'drivers', 'vehicles'));
    }

    public function update(Request $request, TransportBooking $transport)
    {
        $validated = $request->validate([
            'transport_date' => ['required', 'date'],
            'pickup_time' => ['required'],
            'pickup_location' => ['required', 'string', 'max:255'],
            'drop_off_location' => ['required', 'string', 'max:255'],
            'additional_stops' => ['nullable', 'string'],
            'transport_type' => ['required', 'in:airport_pickup,airport_drop,hotel_transfer,intercity,day_tour,other'],
            'driver_id' => ['nullable', 'exists:drivers,id'],
            'vehicle_id' => ['nullable', 'exists:vehicles,id'],
            'driver_name' => ['nullable', 'string', 'max:191'],
            'driver_phone' => ['nullable', 'string', 'max:50'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'driver_cost' => ['nullable', 'numeric', 'min:0'],
            'operational_status' => ['required', 'in:pending,confirmed,driver_assigned,in_progress,completed,cancelled'],
            'notes' => ['nullable', 'string'],
        ]);

        $transport->update([
            'transport_date' => $validated['transport_date'],
            'pickup_time' => $validated['pickup_time'],
            'pickup_location' => $validated['pickup_location'],
            'drop_off_location' => $validated['drop_off_location'],
            'additional_stops' => $validated['additional_stops'],
            'transport_type' => $validated['transport_type'],
            'driver_id' => $validated['driver_id'] ?? null,
            'vehicle_id' => $validated['vehicle_id'] ?? null,
            'driver_name' => $validated['driver_name'],
            'driver_phone' => $validated['driver_phone'],
            'selling_price' => $validated['selling_price'],
            'driver_cost' => $validated['driver_cost'] !== null && $validated['driver_cost'] !== '' ? $validated['driver_cost'] : null,
            'operational_status' => $validated['operational_status'],
            'notes' => $validated['notes'],
        ]);

        ActivityLog::log('updated', $transport, "Transport {$transport->reference_no} updated");

        return redirect()->route('transports.show', $transport->id)->with('success', 'Transport booking updated.');
    }

    public function updateStatus(Request $request, TransportBooking $transport)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,confirmed,driver_assigned,in_progress,completed,cancelled'],
        ]);

        $prev = $transport->operational_status;
        $new = $validated['status'];

        $updateData = ['operational_status' => $new];
        if ($new === 'completed') {
            $updateData['completed_at'] = now();
            $updateData['completed_by_user_id'] = auth()->id();
        }

        $transport->update($updateData);

        ActivityLog::log('status_change', $transport, "Transport {$transport->reference_no} status changed from {$prev} to {$new}");

        return back()->with('success', "Transport status updated to " . ucfirst(str_replace('_', ' ', $new)));
    }

    public function addPayment(Request $request, TransportBooking $transport)
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['required', 'in:cash,bank_transfer,card,other'],
            'transaction_reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        Payment::create([
            'payable_type' => TransportBooking::class,
            'payable_id' => $transport->id,
            'amount' => $validated['amount'],
            'currency' => 'LKR',
            'payment_date' => $validated['payment_date'],
            'payment_method' => $validated['payment_method'],
            'transaction_reference' => $validated['transaction_reference'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'recorded_by_user_id' => auth()->id(),
        ]);

        $transport->syncPaymentsLedger();

        ActivityLog::log('payment_added', $transport, "Recorded payment of {$validated['amount']} LKR for Transport {$transport->reference_no}");

        return back()->with('success', "Payment of " . number_format($validated['amount'], 2) . " LKR recorded successfully.");
    }
}
