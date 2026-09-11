<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GuestParticipantController;
use App\Http\Controllers\ParticipantController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\TrackingController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
});

// Acceso público de invitado: el participante ve sus cuotas con su token, sin registrarse.
Route::get('/p/{participant:token}', [GuestParticipantController::class, 'show'])->name('guest.participant');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('participants', ParticipantController::class)->except('show');
    Route::patch('participants/{participant}/token', [ParticipantController::class, 'regenerarToken'])->name('participants.token');
    Route::resource('purchases', PurchaseController::class);
    Route::resource('services', ServiceController::class);
    Route::patch('services/{service}/cancelar', [ServiceController::class, 'cancelar'])->name('services.cancelar');

    // Seguimiento de cuotas/cargos y marcado de pagos.
    Route::get('seguimiento', [TrackingController::class, 'index'])->name('tracking.index');
    Route::patch('participaciones/{share}/pago', [PaymentController::class, 'toggleShare'])->name('shares.toggle');
    Route::patch('cuotas/{installment}/pago', [PaymentController::class, 'toggleInstallment'])->name('installments.toggle');
    Route::patch('participaciones-servicio/{share}/pago', [PaymentController::class, 'toggleServiceChargeShare'])->name('service-charge-shares.toggle');
    Route::patch('cargos/{charge}/pago', [PaymentController::class, 'toggleServiceCharge'])->name('service-charges.toggle');
});

require __DIR__.'/auth.php';
