<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

//test
Route::get('/check',function(){
    return response()->json(['message' => "Produk", 'success' => true]);
});