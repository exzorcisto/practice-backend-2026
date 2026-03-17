<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Survey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SurveyController extends Controller
{
    public function store(Request $request)
    {
        // 1. Валидация данных опроса
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // 2. Создание записи (author_id берется из токена)
        $survey = Survey::create([
            'title' => $request->title,
            'description' => $request->description,
            'author_id' => $request->user()->id, // ID текущего юзера из Sanctum
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Опрос создан',
            'data' => $survey
        ], 201);
    }
}
