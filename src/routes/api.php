<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SurveyController;
use App\Http\Controllers\Api\ResponseController;
use Illuminate\Support\Facades\Route;

#C:\ProgramData\ComposerSetup\bin\composer.bat
// --- Публичные маршруты ---
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/surveys/{id}/responses', [ResponseController::class, 'store']);
});
// --- Защищенные маршруты (нужен Sanctum токен) ---
Route::middleware('auth:sanctum')->group(function () {

    // Опросы
    Route::get('/surveys', [SurveyController::class, 'index']);
    Route::post('/surveys', [SurveyController::class, 'store']);

    // Выход
    Route::post('/logout', [AuthController::class, 'logout']);
});
