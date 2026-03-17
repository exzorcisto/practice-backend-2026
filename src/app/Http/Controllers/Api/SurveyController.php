<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Survey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SurveyController extends Controller
{
    public function show($id)
    {
        // Загружаем опрос вместе с его вопросами и вариантами (жадная загрузка)
        $survey = \App\Models\Survey::with('questions.options')->findOrFail($id);

        return response()->json($survey);
    }
    /**
     * Создание нового опроса (только для авторов)
     */
    public function store(Request $request)
    {
        $user = $request->user();

        // 1. Проверяем, что это автор (роль 1)
        if ((int)$user->role !== 1) {
            return response()->json(['message' => 'Создавать опросы могут только авторы'], 403);
        }

        // 2. Валидация входных данных (включая приоритет)
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'integer|min:0', // Проверяем твой новый атрибут
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // 3. Создание записи
        $survey = Survey::create([
            'author_id' => $user->id,
            'title' => $request->title,
            'description' => $request->description,
            'status' => 'draft', // По умолчанию всегда черновик
            'priority' => $request->priority ?? 0,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Опрос создан как черновик',
            'data' => $survey
        ], 201);
    }

    /**
     * Список всех опросов (для всех авторизованных)
     */
    public function index()
    {
        // Сортируем по приоритету (от большего к меньшему)
        $surveys = Survey::orderBy('priority', 'desc')->get();
        return response()->json($surveys);
    }
}
