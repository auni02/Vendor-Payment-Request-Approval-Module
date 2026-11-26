<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\PaymentRequestController;
use App\Http\Controllers\PaymentRequestReviewController;
use App\Models\PaymentRequest;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:20,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::redirect('/', '/dashboard');
    Route::get('/dashboard', [PaymentRequestController::class, 'index'])->name('dashboard');

    Route::prefix('payment-requests')->name('payment-requests.')->controller(PaymentRequestController::class)->group(function () {
        Route::get('/create', 'create')->name('create')->can('create', PaymentRequest::class);
        Route::post('/', 'store')->name('store')->can('create', PaymentRequest::class);
        Route::get('/{paymentRequest}', 'show')->name('show')->can('view', 'paymentRequest');
        Route::get('/{paymentRequest}/edit', 'edit')->name('edit')->can('update', 'paymentRequest');
        Route::put('/{paymentRequest}', 'update')->name('update')->can('update', 'paymentRequest');
        Route::get('/{paymentRequest}/attachment', 'attachment')->name('attachment')->can('view', 'paymentRequest');
    });

    Route::prefix('payment-requests/{paymentRequest}')->name('payment-requests.')->controller(PaymentRequestReviewController::class)
        ->middleware('can:review,paymentRequest')
        ->group(function () {
            Route::post('/approve', 'approve')->name('approve');
            Route::post('/reject', 'reject')->name('reject');
        });
});
