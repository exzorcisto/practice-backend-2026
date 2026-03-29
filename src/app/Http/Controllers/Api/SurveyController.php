<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Survey;
use App\Models\SurveyResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SurveyController extends Controller
{
    /**
     * получение конкретного опроса со всей структурой
     */
    public function show($id)
    {
        $survey = Survey::with('questions.options')->findOrFail($id);
        return response()->json($survey);
    }

    /**
     * создание нового опроса (только для авторов)
     */
    public function store(Request $request)
    {
        $user = $request->user();

        // 1. проверяем роль автора
        if ((int)$user->role !== 1) {
            return response()->json(['message' => 'Создавать опросы могут только авторы'], 403);
        }

        // 2. валидация
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // 3. создание записи
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
     * получение опроса для прохождения (только опубликованные)
     */
    public function getForPassing($id)
    {
        $user = auth()->user();

        $alreadyAnswered = SurveyResponse::where('user_id', $user->id)
            ->where('survey_id', $id)
            ->exists();

        if ($alreadyAnswered) {
            return response()->json(['message' => 'Вы уже проходили этот опрос.'], 403);
        }

        $survey = Survey::where('id', $id)
            ->where('status', 'published')
            ->with(['questions.options' => function ($query) {
                $query->orderBy('question_id', 'asc');
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

        // 1. проверка прав: редактировать может только автор
        if ($survey->author_id !== $user->id) {
            return response()->json(['message' => 'Это не ваш опрос.'], 403);
        }

        // 2. жесткая проверка: редактирование текста разрешено только для черновиков
        if ($survey->status !== 'draft') {
            return response()->json([
                'message' => "Нельзя менять текст. Опрос уже находится в статусе: {$survey->status}."
            ], 403);
        }

        // 3. валидация данных
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // 4. обновление полей
        $survey->update($request->only(['title', 'description']));

        return response()->json([
            'status' => 'success',
            'message' => 'Текст опроса успешно обновлен',
            'data' => $survey
        ]);
    }

    /**
     * изменение статуса (draft -> published -> closed)
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
     * список всех опросов с сортировкой по приоритету
     */
    public function index(Request $request)
    {
        // используем try-catch, чтобы вместо белого экрана (500) получать понятную ошибку
        try {
            $query = Survey::query();

            // 1. Фильтрация
            if ($request->has('filter')) {
                $filter = $request->filter;

                if ($filter === 'mine') {
                    // проверка: Если пользователь не залогинен, auth()->id() вернет null
                    if (auth()->check()) {
                        // В твоем проекте поле скорее всего 'author_id', а не 'user_id'
                        $query->where('author_id', auth()->id());
                    } else {
                        return response()->json(['message' => 'Авторизуйтесь, чтобы увидеть свои опросы'], 401);
                    }
                }
                // маппинг твоих статусов: 'active' в базе это 'published'
                elseif ($filter === 'active') {
                    $query->where('status', 'published');
                } elseif (in_array($filter, ['completed', 'closed', 'draft'])) {
                    $query->where('status', $filter);
                }
            }

            // прямой фильтр по статусу (как на твоем скриншоте ?status=closed)
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // 2. сортировка
            $sortBy = $request->input('sort_by', 'created_at');
            $sortDir = $request->input('sort_dir', 'desc');

            // безопасный список полей для сортировки
            $allowedSorts = ['created_at', 'priority', 'title'];

            if ($sortBy === 'responses') {
                // withCount('answers') создаст поле answers_count
                $query->withCount('responses')->orderBy('responses_count', $sortDir);
            } elseif (in_array($sortBy, $allowedSorts)) {
                $query->orderBy($sortBy, $sortDir);
            } else {
                $query->orderBy('created_at', 'desc');
            }

            // 3. пагинация
            $perPage = (int)$request->input('per_page', 10);

            // paginate() в Laravel автоматически подхватывает параметр ?page=... из URL
            return response()->json($query->paginate($perPage));
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ошибка при фильтрации данных',
                'debug' => $e->getMessage()
            ], 500);
        }
    }

    public function analytics($id)
    {
        $user = auth()->user();

        // 1. Проверка роли: только авторы (1) имеют доступ
        if ((int)$user->role !== 1) {
            return response()->json(['message' => 'Доступ к аналитике ограничен.'], 403);
        }

        // жадная загрузка данных
        $survey = Survey::with(['questions.options', 'questions.answers'])->findOrFail($id);

        // 2. проверка владения: автор видит только свой опрос
        if ($survey->author_id !== $user->id) {
            return response()->json(['message' => 'Это не ваш опрос.'], 403);
        }

        // 3. Подсчет уникальных респондентов
        $totalRespondents = DB::table('survey_responses')
            ->where('survey_id', $id)
            ->count();

        // 4. статистика по вопросам
        $statistics = $survey->questions->map(function ($question) {
            $stat = [
                'question_id' => $question->id,
                'text'        => $question->content,
                'type'        => $question->type,
            ];

            if (in_array($question->type, ['radio', 'checkbox', 'select'])) {
                $totalQuestionAnswers = $question->answers->count();
                $stat['options'] = $question->options->map(function ($option) use ($question, $totalQuestionAnswers) {
                    $count = $question->answers->where('option_id', $option->id)->count();
                    return [
                        'option_id'  => $option->id,
                        'text'       => $option->text,
                        'count'      => $count,
                        'percentage' => $totalQuestionAnswers > 0 ? round(($count / $totalQuestionAnswers) * 100, 1) : 0
                    ];
                });
            } elseif ($question->type === 'text') {
                $stat['answers'] = $question->answers->pluck('text_answer')->filter()->values();
            }

            return $stat;
        });

        return response()->json([
            'status' => 'success',
            'data' => [
                'survey_id'         => $survey->id,
                'title'             => $survey->title,
                'total_respondents' => $totalRespondents,
                'statistics'        => $statistics
            ]
        ]);
    }

    public function exportJson($id)
    {
        // используем внутренний вызов метода аналитики, чтобы не дублировать код
        // json()->getData(true) превращает JSON-ответ обратно в ассоциативный массив
        $data = $this->analytics($id)->getData(true);

        return response()->json($data)
            ->header('Content-Disposition', 'attachment; filename="survey_' . $id . '_analytics.json"')
            ->header('Content-Type', 'application/json');
    }
};