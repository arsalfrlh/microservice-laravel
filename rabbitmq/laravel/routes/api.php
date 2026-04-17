<?php

use App\Http\Controllers\AuthApiController;
use App\Http\Controllers\PostApiController;
use App\Http\Controllers\SongApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::apiResource('/song',SongApiController::class);
Route::middleware('auth:sanctum')->group(function(){
    Route::apiResource('/post',PostApiController::class);
});

Route::get('/ai-data/{id}',[PostApiController::class,'aiData']);
Route::post('/login',[AuthApiController::class,'login']);
Route::post('/register',[AuthApiController::class,'register']);