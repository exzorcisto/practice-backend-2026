<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Survey;
use App\Models\Answer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AnswerController extends Controller
{
    public function store(Request $request, $surveyId)
    {
        $user = $request->user();
        $survey = Survey::findOrFail($surveyId);

        // 1. ПРОВЕРКА СТАТУСА: Только опубликованные
        if ($survey->status !== 'published') {
            return response()->json([
                'message' => "Нельзя пройти опрос в статусе: {$survey->status}"
            ], 403);
        }

        // 2. ЗАЩИТА: От повторного прохождения (через таблицу responses)
        $alreadyAnswered = DB::table('survey_responses')
            ->where('user_id', $user->id)
            ->where('survey_id', $surveyId)
            ->exists();

        if ($alreadyAnswered) {
            return response()->json(['message' => 'Вы уже проходили этот опрос.'], 403);
        }

        // 3. Валидация
        $validator = Validator::make($request->all(), [
            'answers' => 'required|array|min:1',
            'answers.*.question_id' => 'required|exists:questions,id',
            'answers.*.option_id' => 'nullable|exists:question_options,id',
            'answers.*.text_answer' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // 4. СОХРАНЕНИЕ (Транзакция для надежности)
        DB::transaction(function () use ($request, $user, $surveyId) {
            // Создаем запись о факте прохождения
            $responseId = DB::table('survey_responses')->insertGetId([
                'user_id' => $user->id,
                'survey_id' => $surveyId,
            ]);

            // Сохраняем все ответы, привязывая их к response_id
            foreach ($request->answers as $ans) {
                DB::table('answers')->insert([
                    'response_id' => $responseId,
                    'question_id' => $ans['question_id'],
                    'option_id'   => $ans['option_id'] ?? null,
                    'text_answer' => $ans['text_answer'] ?? null,
                ]);
            }
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Опрос успешно пройден!'
        ], 201);
    }
}
