<?php

/*
|--------------------------------------------------------------------------
| Routes Billing — abonnements & plans
|--------------------------------------------------------------------------
| Chargé depuis App\Providers\RouteServiceProvider (groupe `web`),
| sans toucher à routes/web.php.
*/

use App\Http\Controllers\Billing\SubscriptionController;
use App\Http\Controllers\Billing\SubscriptionPlanController;
use Illuminate\Support\Facades\Route;

Route::prefix('billing')->name('billing.')->middleware(['has_role:superadmin,admin,finance-manager'])->group(function () {
    // Résiliation / réactivation (état réel en base)
    Route::post('subscriptions/{subscription}/cancel', [SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');
    Route::post('subscriptions/{subscription}/resume', [SubscriptionController::class, 'resume'])->name('subscriptions.resume');

    // Gestion des plans (admin)
    Route::post('plans', [SubscriptionPlanController::class, 'store'])->name('plans.store');
    Route::put('plans/{plan}', [SubscriptionPlanController::class, 'update'])->name('plans.update');
    Route::delete('plans/{plan}', [SubscriptionPlanController::class, 'destroy'])->name('plans.destroy');
});
