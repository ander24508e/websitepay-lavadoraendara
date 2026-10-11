<?php

use App\Http\Controllers\Api\OrderApiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'staff', 'permission:orders.view'])
    ->prefix('orders')
    ->name('api.orders.')
    ->group(function () {
        Route::get('/', [OrderApiController::class, 'index'])->name('index');
        Route::get('/{order}', [OrderApiController::class, 'show'])->name('show');
        Route::patch('/{order}/estado-operativo', [OrderApiController::class, 'updateWorkStatus'])->middleware('permission:orders.update')->name('work-status');
    });
