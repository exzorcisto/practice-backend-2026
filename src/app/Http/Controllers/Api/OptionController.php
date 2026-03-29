<?php

    namespace App\Http\Controllers\Api;

    use App\Http\Controllers\Controller;
    use App\Models\Question;
    use App\Models\Option;
    use Illuminate\Http\Request;

    class OptionController extends Controller
    {
        public function store(Request $request, $questionId)
        {
            $question = Question::findOrFail($questionId);

            // валидация по тз - нельзя добавить варианты к текстовому вопросу
            if ($question->type === 'text') {
                return response()->json([
                    'message' => 'Нельзя добавлять варианты ответов к текстовому вопросу.'
                ], 422);
            }

            $request->validate([
                'options' => 'required|array|min:1',
                'options.*.option_text' => 'required|string',
            ]);

            foreach ($request->options as $optionData) {
                Option::create([
                    'question_id' => $question->id,
                    'option_text' => $optionData['option_text'],
                ]);
            }

            return response()->json(['message' => 'Варианты успешно добавлены'], 201);
        }
    }
