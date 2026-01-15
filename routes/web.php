<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\KeuzedeelController;
use App\Http\Controllers\InschrijvingController;
use App\Http\Controllers\Auth\LoginController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// --- Authentication ---
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// --- Protected routes ---
Route::middleware('auth')->group(function () {

    // Home & keuzedeel info
    Route::get('/', [HomeController::class, 'home'])->name('home');
    Route::get('/keuzedeel/{keuzedeel}', [HomeController::class, 'info'])->name('keuzedeel.info');

    // Student inscriptions
    Route::post('/inschrijven', [InschrijvingController::class, 'store'])->name('inschrijven.store');

    // test routes
    Route::get('/homeCOPYFORCONCEPT', fn() => view('homeCOPYFORCONCEPT'));

    // --- Keuzedeel creation routes ---
    Route::get('/create', [KeuzedeelController::class, 'create'])->name('create');
    Route::post('/keuzedeel/aanmaken', [KeuzedeelController::class, 'store'])->name('keuzedeel.store');
});
