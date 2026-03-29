<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SurveyValidationTest extends TestCase
{
    use RefreshDatabase;

    private function createTestUser($roleId = 2)
    {
        DB::table('role')->insertOrIgnore([
            ['id' => 1, 'name_role' => 'Author'],
            ['id' => 2, 'name_role' => 'Listener']
        ]);

        $userId = DB::table('users')->insertGetId([
            'username' => 'user_' . uniqid(),
            'email' => 'email_' . uniqid() . '@test.com',
            'password_hash' => Hash::make('password'),
            'role' => $roleId,
            'created_at' => now(),
        ]);

        return \App\Models\User::find($userId);
    }

    /** 1. Защита от повторного прохождения */
    public function test_user_cannot_answer_twice()
    {
        $user = $this->createTestUser(2);

        $surveyId = DB::table('surveys')->insertGetId([
            'title' => 'Test Survey',
            'status' => 'published',
            'author_id' => 1,
            'created_at' => now(),
        ]);

        DB::table('survey_responses')->insert([
            'user_id' => $user->id,
            'survey_id' => $surveyId,
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/listener/surveys/{$surveyId}/answers", [
                'answers' => [['question_id' => 1, 'text_answer' => 'Duplicate']]
            ]);

        $response->assertStatus(403);
    }

    /** 2. Нельзя менять опубликованное */
    public function test_cannot_update_published_survey()
    {
        $author = $this->createTestUser(1);
        $surveyId = DB::table('surveys')->insertGetId([
            'title' => 'Published',
            'status' => 'published',
            'author_id' => $author->id,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($author, 'sanctum')
            ->putJson("/api/author/surveys/{$surveyId}", ['title' => 'Changed']);

        $response->assertStatus(403);
    }

    /** 3. Текст обязателен */
    public function test_text_answer_is_required_for_text_type()
    {
        $user = $this->createTestUser(2);
        $surveyId = DB::table('surveys')->insertGetId([
            'title' => 'T',
            'status' => 'published',
            'author_id' => 1,
            'created_at' => now(),
        ]);

        $qId = DB::table('questions')->insertGetId([
            'survey_id' => $surveyId,
            'type' => 'text',
            'content' => 'Sample Question',
            'order_index' => 1
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/listener/surveys/{$surveyId}/answers", [
                'answers' => [['question_id' => $qId, 'text_answer' => '']]
            ]);

        $response->assertStatus(422);
    }

    /** 4. Закрытый опрос */
    public function test_cannot_answer_closed_survey()
    {
        $user = $this->createTestUser(2);
        $surveyId = DB::table('surveys')->insertGetId([
            'title' => 'Closed',
            'status' => 'closed',
            'author_id' => 1,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/listener/surveys/{$surveyId}/answers", [
                'answers' => [['question_id' => 1, 'text_answer' => 'ans']]
            ]);

        $response->assertStatus(403);
    }

    /** 5. Аналитика */
    public function test_listener_denied_analytics_access()
    {
        $listener = $this->createTestUser(2);
        $surveyId = DB::table('surveys')->insertGetId([
            'title' => 'S',
            'status' => 'published',
            'author_id' => 1,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($listener, 'sanctum')
            ->getJson("/api/author/surveys/{$surveyId}/analytics");

        $response->assertStatus(403);
    }
}
