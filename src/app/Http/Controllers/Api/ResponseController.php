<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ResponseController extends Controller
{
    public function store(Request $request, $surveyId)
    {
        $user = $request->user();

        // 1. Проверяем роль (как в твоей схеме роль - это integer)
        if ((int)$user->role !== 2) {
            return response()->json(['message' => 'Голосовать могут только слушатели'], 403);
        }

        // 2. Валидация (убедимся, что ID опроса существует)
        $surveyExists = DB::table('surveys')->where('id', $surveyId)->exists();
        if (!$surveyExists) {
            return response()->json(['message' => 'Опрос не найден'], 404);
        }

        // 3. Проверка на повторное голосование
        $alreadyVoted = DB::table('survey_responses')
            ->where('user_id', $user->id)
            ->where('survey_id', $surveyId)
            ->exists();

        if ($alreadyVoted) {
            return response()->json(['message' => 'Вы уже принимали участие'], 400);
        }

        try {
            DB::beginTransaction();

            // 4. Вставляем в survey_responses (согласно твоей схеме поле submitted_at)
            $responseId = DB::table('survey_responses')->insertGetId([
                'user_id' => $user->id,
                'survey_id' => $surveyId,
                'submitted_at' => now() // Убедись, что это имя есть в миграции!
            ]);

            // 5. Вставляем ответы в таблицу answers
            foreach ($request->answers as $answer) {
                DB::table('answers')->insert([
                    'response_id' => $responseId,
                    'question_id' => $answer['question_id'],
                    // Эти поля могут быть null, если вопрос текстовый или наоборот
                    'option_id'   => isset($answer['option_id']) ? $answer['option_id'] : null,
                    'text_answer' => isset($answer['text_answer']) ? $answer['text_answer'] : null,
                ]);
            }

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Ответ сохранен'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            // Это поможет увидеть реальную причину 500-й ошибки в Insomnia
            return response()->json([
                'status' => 'error',
                'message' => 'Ошибка БД: ' . $e->getMessage()
            ], 500);
        }
    }
}
