<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Survey extends Model
{
    use HasFactory;

    public $timestamps = false;
    
    protected $fillable = [
        'author_id',
        'title',
        'description',
        'status',
        'priority'
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function questions()
    {
        return $this->hasMany(Question::class)->orderBy('order_index');
    }

    public function responses()
    {
        return $this->hasMany(SurveyResponse::class);
    }
}
