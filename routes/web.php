<?php

use App\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DiscountController as AdminDiscountController;
use App\Http\Controllers\Admin\EventController as AdminEventController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\RoomController as AdminRoomController;
use App\Http\Controllers\Admin\ShowtimeController as AdminShowtimeController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SeatHoldController;
use App\Http\Controllers\ShowtimeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/events', [EventController::class, 'index'])->name('events.index');
Route::get('/events/{slug}', [EventController::class, 'show'])->name('events.show');

// Backward compatibility routes for movies
Route::get('/movies', [EventController::class, 'index'])->name('movies.index');
Route::get('/movies/{slug}', [EventController::class, 'show'])->name('movies.show');
Route::get('/showtimes/{showtime}/seats', [ShowtimeController::class, 'seats'])->name('showtimes.seats');
Route::get('/showtimes/{showtime}/seat-status', [ShowtimeController::class, 'seatStatus'])->name('showtimes.seat-status');

Route::middleware('auth')->group(function () {
    Route::post('/showtimes/{showtime}/holds', [SeatHoldController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('showtimes.holds.store');
    Route::delete('/cart/seats/{showtimeSeat}', [SeatHoldController::class, 'destroy'])->name('cart.seats.destroy');
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/checkout', [CheckoutController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('checkout.store');
    Route::get('/bookings', [BookingController::class, 'history'])->name('bookings.history');
});

Route::get('/dashboard', function () {
    if (auth()->user()?->hasRole('admin')) {
        return redirect()->route('admin.dashboard');
    }

    return redirect()->route('home');
})->middleware(['auth'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/pdf', [AdminReportController::class, 'pdf'])->name('reports.pdf');
    Route::resource('categories', AdminCategoryController::class)->except('show');
    Route::resource('events', AdminEventController::class)->except('show');
    Route::resource('showtimes', AdminShowtimeController::class)->except('show');
    Route::get('/rooms', [AdminRoomController::class, 'index'])->name('rooms.index');
    Route::get('/rooms/create', [AdminRoomController::class, 'create'])->name('rooms.create');
    Route::post('/rooms', [AdminRoomController::class, 'store'])->name('rooms.store');
    Route::get('/rooms/{room}/builder', [AdminRoomController::class, 'builder'])->name('rooms.builder');
    Route::get('/rooms/{room}/edit', [AdminRoomController::class, 'edit'])->name('rooms.edit');
    Route::put('/rooms/{room}', [AdminRoomController::class, 'update'])->name('rooms.update');
    Route::post('/rooms/{room}/duplicate', [AdminRoomController::class, 'duplicate'])->name('rooms.duplicate');
    Route::delete('/rooms/{room}', [AdminRoomController::class, 'destroy'])->name('rooms.destroy');
    Route::get('/bookings', [AdminBookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/{booking}', [AdminBookingController::class, 'show'])->name('bookings.show');
    Route::patch('/bookings/{booking}/status', [AdminBookingController::class, 'updateStatus'])->name('bookings.update-status');
    Route::get('/discounts', [AdminDiscountController::class, 'index'])->name('discounts.index');
    Route::post('/discounts', [AdminDiscountController::class, 'store'])->name('discounts.store');
    Route::put('/discounts/{discount}', [AdminDiscountController::class, 'update'])->name('discounts.update');
    Route::delete('/discounts/{discount}', [AdminDiscountController::class, 'destroy'])->name('discounts.destroy');
    Route::patch('/discounts/{discount}/toggle-status', [AdminDiscountController::class, 'toggleStatus'])->name('discounts.toggle-status');
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::patch('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::patch('/users/{user}/toggle-role', [UserController::class, 'toggleRole'])->name('users.toggle-role');
    Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
});

require __DIR__.'/auth.php';

