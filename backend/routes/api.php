<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Versioning
|--------------------------------------------------------------------------
| Current stable API is /v1.
| When upgrading, add new endpoints under /v2 without breaking /v1.
*/

Route::prefix('v1')->group(function () {
    require __DIR__.'/api_v1.php';
});

// Backward compatibility (to be removed once clients are migrated)
Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'version' => 'v1']);
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
