<?php

use App\Http\Controllers\MicroServiceApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/handle',[MicroServiceApiController::class,'handle']);

//contoh web lain microservice yg memerima request kita
Route::post('/ppob',[MicroServiceApiController::class,'ppob']);
Route::post('/payment',[MicroServiceApiController::class,'payment']);
Route::post('/micro',[MicroServiceApiController::class,'micro']);