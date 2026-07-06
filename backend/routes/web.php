<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'name' => config('app.name'),
        'status' => 'ok',
        'message' => 'Posheh API is running. Build the frontend or open /api/v1/plans',
    ]);
});
