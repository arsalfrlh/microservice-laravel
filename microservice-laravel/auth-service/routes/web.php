<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

//test
Route::get('/ping', function(){
    return response()->json(['message' => "Auth", 'success' => true]);
});