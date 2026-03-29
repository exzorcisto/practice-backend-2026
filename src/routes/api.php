<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SurveyController;
use App\Http\Controllers\Api\QuestionController;
use App\Http\Controllers\Api\OptionController;
use App\Http\Controllers\Api\AnswerController;
use Illuminate\Support\Facades\Route;

/* --- публичные маршруты (доступны всем) --- */

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

/* --- защищенные маршруты (требуют токен) --- */
Route::middleware('auth:sanctum')->group(function () {

    // 1. общие маршруты
    Route::get('/surveys', [SurveyController::class, 'index']); // список всех опросов (с сортировкой по приоритету)
    Route::get('/surveys/{id}', [SurveyController::class, 'show']); // детальный просмотр

    // 2. зона автора (Role 1)
    Route::prefix('author')->group(function () {
        Route::get('/surveys/{id}/analytics', [SurveyController::class, 'analytics']);
        Route::get('/surveys/{id}/export', [SurveyController::class, 'exportJson']);
        // управление опросами
        Route::post('/surveys', [SurveyController::class, 'store']); // создать новый опрос (черновик)
        Route::patch('/surveys/{id}/status', [SurveyController::class, 'updateStatus']); // опубликовать/Закрыть
        Route::put('/surveys/{id}', [SurveyController::class, 'update']); // метод PUT или PATCH для обновления

        // Управление структурой (только если статус 'draft')
        Route::post('/surveys/{id}/questions', [QuestionController::class, 'store']); // добавить вопросы
        Route::post('/questions/{id}/options', [OptionController::class, 'store']); // добавить варианты ответов
    });

    // 3. зона слушателя (Role 2)
    Route::prefix('listener')->group(function () {
        // Получение опубликованного опроса для прохождения
        Route::get('/surveys/{id}/take', [SurveyController::class, 'getForPassing']);

        // отправка ответов (с защитой от повторов и проверкой статуса 'published')
        Route::post('/surveys/{id}/answers', [AnswerController::class, 'store']);
    });
});
