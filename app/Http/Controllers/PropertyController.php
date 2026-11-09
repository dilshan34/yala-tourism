<?php

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use App\Models\Property;
use App\Models\Room;
use App\Models\RoomUnavailability;
use App\Services\OccupancyService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PropertyController extends Controller
{
    protected OccupancyService $occupancyService;

    public function __construct(OccupancyService $occupancyService)
    {
        $this->occupancyService = $occupancyService;
    }

    public function index(Request $request)
    {
        $search = $request->input('search');

        $properties = Property::with(['rooms' => fn($q) => $q->withCount(['calendarConnections', 'calendarEvents'])])
            ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
            ->latest()
            ->paginate(10);

        return view('properties.index', compact('properties', 'search'));
    }

    public function create()
    {
        return view('properties.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'property_choice' => ['required', 'string'], // 'wild_pulse', 'yala_bliss', 'other'
            'custom_name' => ['required_if:property_choice,other', 'nullable', 'string', 'max:191'],
            'code' => ['nullable', 'string', 'max:20', 'unique:properties,code'],
            'description' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'color_hex' => ['nullable', 'string', 'max:10'],
        ]);

        $name = match ($validated['property_choice']) {
            'wild_pulse' => 'Wild Pulse',
            'yala_bliss' => 'Yala Bliss',
            default => trim($validated['custom_name']),
        };

        $slug = Str::slug($name);
        $count = Property::where('slug', 'like', "{$slug}%")->count();
        if ($count > 0) {
            $slug = "{$slug}-" . ($count + 1);
        }

        $property = Property::create([
            'name' => $name,
            'slug' => $slug,
            'code' => $validated['code'] ?? strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $name), 0, 3)),
            'description' => $validated['description'] ?? null,
            'address' => $validated['address'] ?? null,
            'is_predefined' => in_array($validated['property_choice'], ['wild_pulse', 'yala_bliss'], true),
            'is_active' => true,
            'color_hex' => $validated['color_hex'] ?? '#0D9488',
        ]);

        return redirect()->route('properties.show', $property->id)->with('success', "Property '{$property->name}' created successfully.");
    }

    public function show(Property $property, Request $request)
    {
        $property->load(['rooms.calendarConnections', 'rooms.unavailabilities']);

        $now = Carbon::now();
        $startDate = $now->copy()->startOfMonth();
        $endDate = $now->copy()->endOfMonth();

        $occupancy = $this->occupancyService->calculateOccupancy($startDate, $endDate, $property->id);

        $recentEvents = CalendarEvent::where('property_id', $property->id)
            ->latest('check_in_date')
            ->take(15)
            ->get();

        return view('properties.show', compact('property', 'occupancy', 'recentEvents'));
    }

    public function edit(Property $property)
    {
        return view('properties.edit', compact('property'));
    }

    public function update(Request $request, Property $property)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'code' => ['nullable', 'string', 'max:20', 'unique:properties,code,' . $property->id],
            'description' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'color_hex' => ['nullable', 'string', 'max:10'],
        ]);

        $property->update([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'description' => $validated['description'],
            'address' => $validated['address'],
            'is_active' => $request->boolean('is_active', true),
            'color_hex' => $validated['color_hex'] ?? $property->color_hex,
        ]);

        return redirect()->route('properties.show', $property->id)->with('success', 'Property updated successfully.');
    }

    public function toggleActive(Property $property)
    {
        $property->update(['is_active' => !$property->is_active]);
        $status = $property->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "Property {$property->name} {$status}.");
    }

    // Room Management under Property
    public function storeRoom(Request $request, Property $property)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'room_reference' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'max_occupancy' => ['required', 'integer', 'min:1'],
        ]);

        $property->rooms()->create([
            'name' => $validated['name'],
            'room_reference' => $validated['room_reference'] ?? null,
            'description' => $validated['description'] ?? null,
            'max_occupancy' => $validated['max_occupancy'],
            'is_active' => true,
        ]);

        return back()->with('success', "Room '{$validated['name']}' added to {$property->name}.");
    }

    public function updateRoom(Request $request, Room $room)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'room_reference' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'max_occupancy' => ['required', 'integer', 'min:1'],
            'is_active' => ['boolean'],
        ]);

        $room->update([
            'name' => $validated['name'],
            'room_reference' => $validated['room_reference'],
            'description' => $validated['description'],
            'max_occupancy' => $validated['max_occupancy'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', "Room '{$room->name}' updated.");
    }

    public function storeMaintenanceBlock(Request $request, Room $room)
    {
        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        RoomUnavailability::create([
            'room_id' => $room->id,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'reason' => $validated['reason'],
            'created_by_user_id' => auth()->id(),
        ]);

        return back()->with('success', 'Maintenance dates blocked successfully.');
    }
}
