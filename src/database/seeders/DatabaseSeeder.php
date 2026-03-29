<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Роли
        DB::table('role')->upsert([
            ['id' => 1, 'name_role' => 'author'],
            ['id' => 2, 'name_role' => 'listener'],
        ], ['id'], ['name_role']);

        // 2. Пользователи
        $authorId = DB::table('users')->insertGetId([
            'username' => 'exzorcisto',
            'email' => 'author@test.com',
            'password_hash' => Hash::make('password'),
            'role' => 1,
        ]);

        DB::table('users')->insert([
            'username' => 'student_listener',
            'email' => 'listener@test.com',
            'password_hash' => Hash::make('password'),
            'role' => 2,
        ]);

        // 3. Опрос
        $surveyId = DB::table('surveys')->insertGetId([
            'author_id' => $authorId,
            'title' => 'Проверка системы',
            'description' => 'Тестирование ответов слушателя',
            'status' => 'active'
        ]);

        // 4. Первый вопрос (ID будет 1) - Radio
        $q1 = DB::table('questions')->insertGetId([
            'survey_id' => $surveyId,
            'type' => 'radio',
            'content' => 'Как работает API?',
            'order_index' => 1
        ]);

        DB::table('question_options')->insert([
            ['question_id' => $q1, 'content' => 'Отлично'],
            ['question_id' => $q1, 'content' => 'Нормально'],
        ]);

        // 5. Второй вопрос (ID будет 2) - Text
        DB::table('questions')->insert([
            'survey_id' => $surveyId,
            'type' => 'text',
            'content' => 'Напишите свой отзыв',
            'order_index' => 2
        ]);
    }
}
