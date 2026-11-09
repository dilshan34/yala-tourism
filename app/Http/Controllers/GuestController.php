<?php

namespace App\Http\Controllers;

use App\Models\Guest;
use Illuminate\Http\Request;

class GuestController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $country = $request->query('country');

        $query = Guest::withCount(['safariBookings', 'transportBookings'])
            ->latest();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('passport_number', 'like', "%{$search}%");
            });
        }

        if ($country && $country !== 'all') {
            $query->where('country', $country);
        }

        $guests = $query->paginate(15);
        $countries = Guest::distinct('country')->pluck('country');

        return view('guests.index', compact('guests', 'countries', 'search', 'country'));
    }

    public function create()
    {
        return view('guests.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:191'],
            'phone_number' => ['required', 'string', 'max:50'],
            'whatsapp_number' => ['nullable', 'string', 'max:50'],
            'country' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:191'],
            'passport_number' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        // Duplicate phone check warning
        $existing = Guest::where('phone_number', $validated['phone_number'])->first();
        if ($existing) {
            return redirect()->route('guests.show', $existing->id)
                ->with('info', "A guest record with phone number {$validated['phone_number']} already exists.");
        }

        $guest = Guest::create($validated);

        return redirect()->route('guests.show', $guest->id)
            ->with('success', "Guest '{$guest->full_name}' added to directory.");
    }

    public function show(Guest $guest)
    {
        $guest->load([
            'safariBookings.property',
            'safariBookings.payments',
            'transportBookings.property',
            'transportBookings.payments',
        ]);

        return view('guests.show', compact('guest'));
    }

    public function edit(Guest $guest)
    {
        return view('guests.edit', compact('guest'));
    }

    public function update(Request $request, Guest $guest)
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:191'],
            'phone_number' => ['required', 'string', 'max:50'],
            'whatsapp_number' => ['nullable', 'string', 'max:50'],
            'country' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:191'],
            'passport_number' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        $guest->update($validated);

        return redirect()->route('guests.show', $guest->id)->with('success', 'Guest details updated.');
    }
}
