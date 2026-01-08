<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Models\Post;

Route::get('/welcome', function () {
    return view('welcome');
});

Route::get('/', [HomeController::class, 'home'])->name('home');

Route::get('/keuzedeel/{keuzedeel}', [HomeController::class, 'info'])->name('keuzedeel.info');

Route::get('/homeCOPYFORCONCEPT', function() {
    return view('homeCOPYFORCONCEPT');
});

Route::get('/newposttest', function() {
    $x = new Post();
    $x->title = "Mijn titel";
    $x->body = "Dit is de inhoud";
    $x->save();
    return "done";
});
