<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::get('/dashboard', function () {
    return redirect('/');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::prefix('crm')->middleware(['auth.laravel-crm'])->group(function () {
        Route::get('lead-email-settings', [\App\Http\Controllers\LeadEmailSettingsController::class, 'edit'])
            ->name('lead-email-settings.edit')
            ->middleware(['can:update,VentureDrake\LaravelCrm\Models\Setting']);
        Route::patch('lead-email-settings', [\App\Http\Controllers\LeadEmailSettingsController::class, 'update'])
            ->name('lead-email-settings.update')
            ->middleware(['can:update,VentureDrake\LaravelCrm\Models\Setting']);
    });
});

require __DIR__.'/auth.php';
