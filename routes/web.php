<?php

use Illuminate\Support\Facades\Route;
use App\Models\Post;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/newposttest', function(){

    $x = new Post();
    $x->title = "Mijn titel";
    $x->body = "Dit is de inhoud";
    $x->save();

    return "done";
});
