<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\ConnectionController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\InterventionRequestController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TechnicianController;
use App\Http\Controllers\User\CohortController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
 // Correction de la route API Resource


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:web')->prefix('users')->name('api.users.')->group(function () {
    Route::get('cohorts/{cohort}', [CohortController::class, 'show'])->name('cohorts.show');
});
