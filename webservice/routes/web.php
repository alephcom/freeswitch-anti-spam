<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\CallController;

Route::get('/', function () {

    return response('Nothing to see, move on.');
});

Route::get('/call', [CallController::class, 'call']);
Route::post('/call', [CallController::class, 'call']);
