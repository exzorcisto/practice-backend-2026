<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ResponseController extends Controller
{
    public function store(Request $request, $surveyId)
    {
        $user = $request->user();

        // 1. проверяем роль
        if ((int)$user->role !== 2) {
            return response()->json(['message' => 'Голосовать могут только слушатели'], 403);
        }

        // 2. валидация (убедимся, что id опроса существует)
        $surveyExists = DB::table('surveys')->where('id', $surveyId)->exists();
        if (!$surveyExists) {
            return response()->json(['message' => 'Опрос не найден'], 404);
        }

        // 3. проверка на повторное голосование
        $alreadyVoted = DB::table('survey_responses')
            ->where('user_id', $user->id)
            ->where('survey_id', $surveyId)
            ->exists();

        if ($alreadyVoted) {
            return response()->json(['message' => 'Вы уже принимали участие'], 400);
        }

        try {
            DB::beginTransaction();

            // 4. вставляем в survey_responses
            $responseId = DB::table('survey_responses')->insertGetId([
                'user_id' => $user->id,
                'survey_id' => $surveyId,
                'submitted_at' => now()
            ]);

            // 5. вставляем ответы в таблицу answers
            foreach ($request->answers as $answer) {
                DB::table('answers')->insert([
                    'response_id' => $responseId,
                    'question_id' => $answer['question_id'],
                    // эти поля могут быть null, если вопрос текстовый или наоборот
                    'option_id'   => isset($answer['option_id']) ? $answer['option_id'] : null,
                    'text_answer' => isset($answer['text_answer']) ? $answer['text_answer'] : null,
                ]);
            }

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Ответ сохранен'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Ошибка БД: ' . $e->getMessage()
            ], 500);
        }
    }
}
