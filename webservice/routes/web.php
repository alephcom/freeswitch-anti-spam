<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\CallController;

Route::get('/', function () {

    return response('Nothing to see, move on.');
});

Route::get('call', [CallController::class, 'call']);
Route::post('call', [CallController::class, 'call']);

route::get('stats', [CallController::class, 'callStats']);
route::get('clid_purge/{clid}', [CallController::class, 'clidPurge']);
