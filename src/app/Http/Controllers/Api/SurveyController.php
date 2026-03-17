<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Survey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SurveyController extends Controller
{
    /**
     * Получение конкретного опроса со всей структурой
     */
    public function show($id)
    {
        $survey = Survey::with('questions.options')->findOrFail($id);
        return response()->json($survey);
    }

    /**
     * Создание нового опроса (только для авторов)
     */
    public function store(Request $request)
    {
        $user = $request->user();

        // 1. Проверяем роль автора
        if ((int)$user->role !== 1) {
            return response()->json(['message' => 'Создавать опросы могут только авторы'], 403);
        }

        // 2. Валидация
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // 3. Создание записи
        $survey = Survey::create([
            'author_id' => $user->id,
            'title' => $request->title,
            'description' => $request->description,
            'status' => 'draft',
            'priority' => $request->priority ?? 0,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Опрос создан как черновик',
            'data' => $survey
        ], 201);
    }

    /**
     * Получение опроса для прохождения (только опубликованные)
     */
    public function getForPassing($id)
    {
        $user = auth()->user();

        // Исправляем запрос: ищем в survey_responses, а не в answers
        $alreadyAnswered = \Illuminate\Support\Facades\DB::table('survey_responses')
            ->where('user_id', $user->id)
            ->where('survey_id', $id)
            ->exists();

        if ($alreadyAnswered) {
            return response()->json(['message' => 'Вы уже проходили этот опрос.'], 403);
        }

        $survey = Survey::where('id', $id)
            ->where('status', 'published')
            ->with(['questions.options' => function ($query) {
                $query->orderBy('order_index', 'asc');
            }])
            ->first();

        if (!$survey) {
            return response()->json(['message' => 'Опрос не найден или еще не опубликован.'], 404);
        }

        return response()->json(['status' => 'success', 'data' => $survey]);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        $survey = Survey::findOrFail($id);

        // 1. Проверка прав: редактировать может только автор
        if ($survey->author_id !== $user->id) {
            return response()->json(['message' => 'Это не ваш опрос.'], 403);
        }

        // 2. ЖЕСТКАЯ ПРОВЕРКА: Редактирование текста разрешено только для черновиков
        if ($survey->status !== 'draft') {
            return response()->json([
                'message' => "Нельзя менять текст. Опрос уже находится в статусе: {$survey->status}."
            ], 403);
        }

        // 3. Валидация данных
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // 4. Обновление полей
        $survey->update($request->only(['title', 'description']));

        return response()->json([
            'status' => 'success',
            'message' => 'Текст опроса успешно обновлен',
            'data' => $survey
        ]);
    }

    /**
     * Изменение статуса (draft -> published -> closed)
     */
    public function updateStatus(Request $request, $id)
    {
        $survey = Survey::findOrFail($id);
        $newStatus = $request->input('status');

        $statuses = [
            'draft' => 1,
            'published' => 2,
            'closed' => 3
        ];

        if (!isset($statuses[$newStatus])) {
            return response()->json(['message' => "Статус '{$newStatus}' недопустим"], 422);
        }

        $currentWeight = $statuses[$survey->status] ?? 0;

        // Логика "стрелочка не поворачивается"
        if ($statuses[$newStatus] <= $currentWeight) {
            return response()->json([
                'message' => "Нельзя вернуться к статусу {$newStatus}. Текущий статус: {$survey->status}."
            ], 422);
        }

        $survey->status = $newStatus;
        $survey->save();

        return response()->json([
            'message' => "Статус успешно изменен на {$newStatus}",
            'survey' => $survey
        ]);
    }

    /**
     * Список всех опросов с сортировкой по приоритету
     */
    public function index()
    {
        $surveys = Survey::orderBy('priority', 'desc')->get();
        return response()->json($surveys);
    }
}
