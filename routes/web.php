<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\KeuzedeelController;
use App\Http\Controllers\InschrijvingController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CsvUploadController;
use App\Http\Controllers\StudentController;

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
    Route::delete('/uitschrijven', [InschrijvingController::class, 'destroy'])->name('uitschrijven.destroy');

    // test routes
    Route::get('/homeCOPYFORCONCEPT', fn() => view('homeCOPYFORCONCEPT'));
    
    // Student-specific routes
    // Route::middleware(['student'])->group(function () {
        Route::get('/profile', [HomeController::class, 'profile'])->name('profile');
        Route::post('/inschrijven', [InschrijvingController::class, 'store'])->name('inschrijven.store');
        Route::get('/more-options', [InschrijvingController::class, 'moreOptionsIndex'])->name('more-options.index');
        Route::post('/more-options', [InschrijvingController::class, 'moreOptionsStore'])->name('more-options.store');
    // });

    // Admin-only routes
    // Route::middleware(['admin'])->group(function () {
        Route::get('/create', [KeuzedeelController::class, 'create'])->name('create');
        Route::post('/keuzedeel/aanmaken', [KeuzedeelController::class, 'store'])->name('keuzedeel.store');
        Route::get('/csv-upload', [CsvUploadController::class, 'index']);
        Route::post('/csv-upload', [CsvUploadController::class, 'store'])->name('upload');
        Route::get('/keuzedeel/{keuzedeel}/edit', [KeuzedeelController::class, 'edit'])->name('keuzedeel.edit');
        Route::put('/keuzedeel/{keuzedeel}', [KeuzedeelController::class, 'update'])->name('keuzedeel.update');
        Route::post('keuzedeel/{keuzedeel}/toggle-actief', [KeuzedeelController::class, 'toggleActief'])->name('keuzedeel.toggleActief');
        Route::delete('/keuzedeel/{keuzedeel}', [KeuzedeelController::class, 'destroy'])->name('keuzedeel.destroy');

        Route::get('/studentoverzicht', [StudentController::class, 'index'])->name('studentoverzicht');


    // });
    Route::get('/students', [StudentController::class, 'index'])->name('students.index');



});


