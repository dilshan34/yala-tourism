<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CalendarConnectionController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DriverVehicleController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\GuestController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SafariController;
use App\Http\Controllers\TransportController;
use Illuminate\Support\Facades\Route;

// Authentication Routes
Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout'])->name('logout');

// Authenticated Business Platform Routes
Route::middleware(['auth'])->group(function () {
    // Root & Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Central Business Calendar
    Route::prefix('calendar')->name('calendar.')->group(function () {
        Route::get('/', [CalendarController::class, 'index'])->name('index');
        Route::get('/events', [CalendarController::class, 'events'])->name('events');
    });

    // Property & Room Management
    Route::prefix('properties')->name('properties.')->group(function () {
        Route::get('/', [PropertyController::class, 'index'])->name('index');
        Route::get('/create', [PropertyController::class, 'create'])->name('create');
        Route::post('/', [PropertyController::class, 'store'])->name('store');
        Route::get('/{property}', [PropertyController::class, 'show'])->name('show');
        Route::get('/{property}/edit', [PropertyController::class, 'edit'])->name('edit');
        Route::put('/{property}', [PropertyController::class, 'update'])->name('update');
        Route::post('/{property}/toggle-active', [PropertyController::class, 'toggleActive'])->name('toggle-active');

        // Rooms
        Route::post('/{property}/rooms', [PropertyController::class, 'storeRoom'])->name('rooms.store');
        Route::put('/rooms/{room}', [PropertyController::class, 'updateRoom'])->name('rooms.update');
        Route::post('/rooms/{room}/maintenance', [PropertyController::class, 'storeMaintenanceBlock'])->name('rooms.maintenance');
    });

    // Calendar Connections & iCal Sync
    Route::prefix('calendar-connections')->name('calendar-connections.')->group(function () {
        Route::get('/', [CalendarConnectionController::class, 'index'])->name('index');
        Route::post('/', [CalendarConnectionController::class, 'store'])->name('store');
        Route::put('/{connection}', [CalendarConnectionController::class, 'update'])->name('update');
        Route::delete('/{connection}', [CalendarConnectionController::class, 'destroy'])->name('destroy');
        Route::post('/test', [CalendarConnectionController::class, 'test'])->name('test');
        Route::post('/{connection}/sync', [CalendarConnectionController::class, 'syncNow'])->name('sync');
        Route::get('/sync-all', [CalendarConnectionController::class, 'syncAll'])->name('sync-all');

        // Deduplication & Conflict Overrides
        Route::post('/duplicates/{primaryEvent}/{secondaryEvent}/confirm', [CalendarConnectionController::class, 'confirmDuplicate'])->name('duplicates.confirm');
        Route::post('/duplicates/{event1}/{event2}/conflict', [CalendarConnectionController::class, 'markAsConflict'])->name('duplicates.conflict');
    });

    // Safari Management
    Route::prefix('safaris')->name('safaris.')->group(function () {
        Route::get('/', [SafariController::class, 'index'])->name('index');
        Route::get('/create', [SafariController::class, 'create'])->name('create');
        Route::post('/', [SafariController::class, 'store'])->name('store');
        Route::get('/{safari}', [SafariController::class, 'show'])->name('show');
        Route::get('/{safari}/edit', [SafariController::class, 'edit'])->name('edit');
        Route::put('/{safari}', [SafariController::class, 'update'])->name('update');
        Route::post('/{safari}/status', [SafariController::class, 'updateStatus'])->name('status');
        Route::post('/{safari}/payment', [SafariController::class, 'addPayment'])->name('payment');
    });

    // Transport Management
    Route::prefix('transports')->name('transports.')->group(function () {
        Route::get('/', [TransportController::class, 'index'])->name('index');
        Route::get('/create', [TransportController::class, 'create'])->name('create');
        Route::post('/', [TransportController::class, 'store'])->name('store');
        Route::get('/{transport}', [TransportController::class, 'show'])->name('show');
        Route::get('/{transport}/edit', [TransportController::class, 'edit'])->name('edit');
        Route::put('/{transport}', [TransportController::class, 'update'])->name('update');
        Route::post('/{transport}/status', [TransportController::class, 'updateStatus'])->name('status');
        Route::post('/{transport}/payment', [TransportController::class, 'addPayment'])->name('payment');
    });

    // Guest Directory
    Route::prefix('guests')->name('guests.')->group(function () {
        Route::get('/', [GuestController::class, 'index'])->name('index');
        Route::get('/create', [GuestController::class, 'create'])->name('create');
        Route::post('/', [GuestController::class, 'store'])->name('store');
        Route::get('/{guest}', [GuestController::class, 'show'])->name('show');
        Route::get('/{guest}/edit', [GuestController::class, 'edit'])->name('edit');
        Route::put('/{guest}', [GuestController::class, 'update'])->name('update');
    });

    // Drivers & Vehicles Management
    Route::prefix('drivers-vehicles')->name('drivers-vehicles.')->group(function () {
        Route::get('/', [DriverVehicleController::class, 'index'])->name('index');
        Route::post('/drivers', [DriverVehicleController::class, 'storeDriver'])->name('drivers.store');
        Route::post('/drivers/{driver}/toggle', [DriverVehicleController::class, 'toggleDriver'])->name('drivers.toggle');
        Route::post('/vehicles', [DriverVehicleController::class, 'storeVehicle'])->name('vehicles.store');
        Route::post('/vehicles/{vehicle}/toggle', [DriverVehicleController::class, 'toggleVehicle'])->name('vehicles.toggle');
        Route::post('/operators', [DriverVehicleController::class, 'storeOperator'])->name('operators.store');
    });

    // Financial Management
    Route::prefix('finances')->name('finances.')->group(function () {
        Route::get('/', [FinanceController::class, 'index'])->name('index');
    });

    // Reports & Analytics
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/export-excel', [ReportController::class, 'exportExcel'])->name('export-excel');
        Route::get('/export-pdf', [ReportController::class, 'exportPdf'])->name('export-pdf');
    });

    // Notifications & Reminders Center
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('read');
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
        Route::post('/trigger-check', [NotificationController::class, 'triggerCheck'])->name('trigger-check');
    });
});
