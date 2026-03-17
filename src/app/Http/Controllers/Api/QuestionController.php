<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\Survey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class QuestionController extends Controller
{
    /**
     * Добавление вопросов к опросу
     */
    public function store(Request $request, $surveyId)
    {
        $user = $request->user();

        // 1. Поиск опроса (если не найден — 404 автоматически)
        $survey = Survey::findOrFail($surveyId);

        // 2. Проверка прав: только автор может менять структуру
        if ((int)$user->role !== 1 || $survey->author_id !== $user->id) {
            return response()->json(['message' => 'Доступ запрещен. Это не ваш опрос.'], 403);
        }

        // 3. ФИЧА: Запрет редактирования структуры опубликованного/закрытого опроса
        if ($survey->status !== 'draft') {
            return response()->json([
                'message' => "Нельзя изменять структуру. Опрос уже в статусе: {$survey->status}."
            ], 403);
        }

        // 4. Валидация входящего массива вопросов
        $validator = Validator::make($request->all(), [
            'questions' => 'required|array|min:1',
            'questions.*.type' => 'required|in:radio,checkbox,text',
            'questions.*.content' => 'required|string',
            'questions.*.order_index' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // 5. Сохранение вопросов
        $results = [];
        foreach ($request->questions as $qData) {
            $results[] = Question::create([
                'survey_id'   => $survey->id,
                'type'        => $qData['type'],
                'content'     => $qData['content'],
                'order_index' => $qData['order_index'],
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Вопросы успешно добавлены к черновику',
            'data' => $results
        ], 201);
    }
}
