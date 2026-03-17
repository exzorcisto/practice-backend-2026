<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\Survey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class QuestionController extends Controller
{
    public function store(Request $request, $surveyId)
    {
        $user = $request->user();

        // 1. Проверка роли (1 - автор по твоему сидеру)
        if ((int)$user->role !== 1) {
            return response()->json(['message' => 'Доступ запрещен. Вы не автор.'], 403);
        }

        // 2. Проверка существования опроса и прав собственности
        $survey = Survey::find($surveyId);
        if (!$survey) {
            return response()->json(['message' => 'Опрос не найден'], 404);
        }

        if ($survey->author_id !== $user->id) {
            return response()->json(['message' => 'Это не ваш опрос!'], 403);
        }

        // 3. Валидация входных данных
        $validator = Validator::make($request->all(), [
            'questions' => 'required|array|min:1',
            'questions.*.type' => 'required|in:radio,checkbox,text',
            'questions.*.content' => 'required|string',
            'questions.*.order_index' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // 4. Сохранение вопросов
        $results = [];
        foreach ($request->questions as $qData) {
            $results[] = Question::create([
                'survey_id' => $surveyId,
                'type' => $qData['type'],
                'content' => $qData['content'],
                'order_index' => $qData['order_index'],
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Вопросы успешно добавлены',
            'data' => $results
        ], 201);
    }
}
