<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\SafariOperator;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class DriverVehicleController extends Controller
{
    public function index()
    {
        $drivers = Driver::with('vehicles')->withCount(['safariBookings', 'transportBookings'])->latest()->get();
        $vehicles = Vehicle::with('defaultDriver')->latest()->get();
        $operators = SafariOperator::withCount('safariBookings')->latest()->get();

        return view('drivers-vehicles.index', compact('drivers', 'vehicles', 'operators'));
    }

    public function storeDriver(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'phone_number' => ['required', 'string', 'max:50'],
            'whatsapp_number' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        Driver::create([
            'name' => $validated['name'],
            'phone_number' => $validated['phone_number'],
            'whatsapp_number' => $validated['whatsapp_number'] ?? $validated['phone_number'],
            'notes' => $validated['notes'] ?? null,
            'is_active' => true,
        ]);

        return back()->with('success', "Driver '{$validated['name']}' added.");
    }

    public function storeVehicle(Request $request)
    {
        $validated = $request->validate([
            'registration_number' => ['required', 'string', 'max:50', 'unique:vehicles,registration_number'],
            'vehicle_type' => ['required', 'string', 'max:100'],
            'passenger_capacity' => ['required', 'integer', 'min:1'],
            'default_driver_id' => ['nullable', 'exists:drivers,id'],
            'description' => ['nullable', 'string'],
        ]);

        Vehicle::create([
            'registration_number' => $validated['registration_number'],
            'vehicle_type' => $validated['vehicle_type'],
            'passenger_capacity' => $validated['passenger_capacity'],
            'default_driver_id' => $validated['default_driver_id'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_active' => true,
        ]);

        return back()->with('success', "Vehicle '{$validated['registration_number']}' added.");
    }

    public function storeOperator(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'contact_person' => ['nullable', 'string', 'max:191'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:191'],
        ]);

        SafariOperator::create([
            'name' => $validated['name'],
            'contact_person' => $validated['contact_person'] ?? null,
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'is_active' => true,
        ]);

        return back()->with('success', "Safari Operator '{$validated['name']}' added.");
    }

    public function toggleDriver(Driver $driver)
    {
        $driver->update(['is_active' => !$driver->is_active]);
        return back()->with('success', 'Driver status updated.');
    }

    public function toggleVehicle(Vehicle $vehicle)
    {
        $vehicle->update(['is_active' => !$vehicle->is_active]);
        return back()->with('success', 'Vehicle status updated.');
    }
}
