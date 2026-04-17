<?php

use App\Http\Controllers\VideoApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/video',[VideoApiController::class,'index']);
Route::post('/video',[VideoApiController::class,'store']);
Route::post('/video/done',[VideoApiController::class,'done']);