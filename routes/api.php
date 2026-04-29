<?php

use App\Http\Controllers\PresenceController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('scan-qr', [PresenceController::class, 'scan']);
});