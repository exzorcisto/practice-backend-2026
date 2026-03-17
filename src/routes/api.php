<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SurveyController;
use App\Http\Controllers\Api\QuestionController;
use App\Http\Controllers\Api\OptionController;
use App\Http\Controllers\Api\AnswerController;
use Illuminate\Support\Facades\Route;

/* --- Публичные маршруты (доступны всем) --- */

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

/* --- Защищенные маршруты (требуют токен) --- */
Route::middleware('auth:sanctum')->group(function () {

    // 1. Общие маршруты
    Route::get('/surveys', [SurveyController::class, 'index']); // Список всех опросов (с сортировкой по приоритету)
    Route::get('/surveys/{id}', [SurveyController::class, 'show']); // Детальный просмотр
    Route::post('/logout', [AuthController::class, 'logout']);

    // 2. Зона АВТОРА (Role 1)
    Route::prefix('author')->group(function () {
        // Управление опросами
        Route::post('/surveys', [SurveyController::class, 'store']); // Создать новый опрос (черновик)
        Route::patch('/surveys/{id}/status', [SurveyController::class, 'updateStatus']); // Опубликовать/Закрыть
        Route::put('/surveys/{id}', [SurveyController::class, 'update']); // Метод PUT или PATCH для обновления

        // Управление структурой (только если статус 'draft')
        Route::post('/surveys/{id}/questions', [QuestionController::class, 'store']); // Добавить вопросы
        Route::post('/questions/{id}/options', [OptionController::class, 'store']); // Добавить варианты ответов
    });

    // 3. Зона СЛУШАТЕЛЯ (Role 2)
    Route::prefix('listener')->group(function () {
        // Получение опубликованного опроса для прохождения
        Route::get('/surveys/{id}/take', [SurveyController::class, 'getForPassing']);

        // Отправка ответов (с защитой от повторов и проверкой статуса 'published')
        Route::post('/surveys/{id}/answers', [AnswerController::class, 'store']);
    });
});
