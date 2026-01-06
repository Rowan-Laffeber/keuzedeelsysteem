<?php

use Illuminate\Support\Facades\Route;
use App\Models\Post;

Route::get('/welcome', function () {
    return view('welcome');
});

Route::get('/', function () {
    return view('home');
});
Route::get('/keuzedeel-info', function () {
    return view('keuzedeel-info');
});


Route::get('/newposttest', function(){

    $x = new Post();
    $x->title = "Mijn titel";
    $x->body = "Dit is de inhoud";
    $x->save();

    return "done";
});
