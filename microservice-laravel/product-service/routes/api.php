<?php

use App\Http\Controllers\BarangApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware(['verify'])->group(function(){
    Route::get('/barang',[BarangApiController::class,'index']);
    Route::post('/barang/tambah',[BarangApiController::class,'create']);
    Route::post('/barang/update',[BarangApiController::class,'update']);
});