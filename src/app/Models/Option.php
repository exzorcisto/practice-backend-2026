<?php

namespace App\Models; // Проверь, нет ли тут ошибки в слове Models

use Illuminate\Database\Eloquent\Model;

class Option extends Model
{
    public $timestamps = false; // Чтобы не было ошибки 500 из-за updated_at

    protected $fillable = [
        'question_id',
        'option_text'
    ];
}
