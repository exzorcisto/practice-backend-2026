<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * @var string
     */
    protected $table = 'users';

    /**
     * @var bool

    */public $timestamps = false;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'email',
        'password_hash',
        'role',
    ];

    /**
     * @var array<int, string>
     */
    protected $hidden = [
        'password_hash',
    ];

    public function getRememberTokenName()
    {
        return null;
    }
    public function setRememberToken($value) {}
    public function getRememberToken()
    {
        return null;
    }

    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    /**
     * связь с ролью.
     */
    public function roleData()
    {
        return $this->belongsTo(Role::class, 'role');
    }

    // связь с созданными опросами.
    public function surveys()
    {
        return $this->hasMany(Survey::class, 'author_id');
    }


    // проверка, является ли пользователь автором.
    public function isAuthor()
    {
        return (int)$this->role === 1;
    }

    // проверка, является ли пользователь слушателем.
    public function isListener()
    {
        return (int)$this->role === 2;
    }
}
