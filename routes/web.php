<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Models\Post;
use App\Http\Controllers\Auth\LoginController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Hier definieer je alle web-routes van je applicatie.
| Routes binnen de 'auth' middleware zijn alleen zichtbaar voor ingelogde gebruikers.
| Als iemand niet is ingelogd, wordt hij automatisch naar de login-pagina gestuurd.
|
*/

// --- Auth Routes ---
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// --- Protected Routes: alleen toegankelijk voor ingelogde gebruikers ---
Route::middleware('auth')->group(function () {

    // Home pagina
    Route::get('/', [HomeController::class, 'home'])->name('home');

    // Keuzedeel info pagina
    Route::get('/keuzedeel/{keuzedeel}', [HomeController::class, 'info'])->name('keuzedeel.info');

    // Conceptpagina (optioneel)
    Route::get('/homeCOPYFORCONCEPT', function() {
        return view('homeCOPYFORCONCEPT');
    });

    // Testpost route (voor testdoeleinden)
    Route::get('/newposttest', function() {
        $x = new Post();
        $x->title = "Mijn titel";
        $x->body = "Dit is de inhoud";
        $x->save();
        return "done";
    });
});
