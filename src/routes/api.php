<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SurveyController;
use App\Http\Controllers\Api\ResponseController;
use App\Http\Controllers\Api\QuestionController;
use App\Http\Controllers\Api\OptionController;
use Illuminate\Support\Facades\Route;

/* --- Публичные маршруты (доступны всем) --- */

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/surveys/{id}', [SurveyController::class, 'show']);

/* --- Защищенные маршруты (требуют токен) --- */
Route::middleware('auth:sanctum')->group(function () {

    // 1. Общие маршруты (и для Автора, и для Слушателя)
    Route::get('/surveys', [SurveyController::class, 'index']); // Посмотреть список
    Route::post('/logout', [AuthController::class, 'logout']);

    // 2. Зона АВТОРА (Role 1)
    // В идеале сюда вешается middleware:role:1, но пока разделим логически комментариями
    Route::prefix('author')->group(function () {
        Route::post('/questions/{id}/options', [OptionController::class, 'store']);
        Route::get('/surveys', [SurveyController::class, 'index']); // Создать опрос
        Route::post('/surveys', [SurveyController::class, 'store']); // Создать опрос
        Route::post('/surveys/{id}/questions', [QuestionController::class, 'store']); // Добавить вопросы
    });

    // 3. Зона СЛУШАТЕЛЯ (Role 2)
    Route::prefix('listener')->group(function () {
        Route::post('/surveys/{id}/responses', [ResponseController::class, 'store']); // Проголосовать
    });
});
