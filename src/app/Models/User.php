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
     * Имя таблицы в БД
     * @var string
     */
    protected $table = 'users';

    /**
     * Отключаем стандартные timestamps (created_at/updated_at), 
     * так как в схеме только один созданный вручную created_at.
     * Если оставишь true, Laravel будет пытаться обновить updated_at и упадет.
     * @var bool

    public $timestamps = false;

    /**
     * Атрибуты, для которых разрешено массовое заполнение.
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'email',
        'password_hash',
        'role',
    ];

    /**
     * Атрибуты, которые должны быть скрыты при преобразовании в массив или JSON.
     * @var array<int, string>
     */
    protected $hidden = [
        'password_hash',
    ];

    /**
     * Переопределяем метод для получения пароля.
     * Это критически важно для работы Auth::attempt() и Hash::check().
     */
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    /**
     * Связь с ролью.
     */
    public function roleData()
    {
        return $this->belongsTo(Role::class, 'role');
    }

    // Связь с созданными опросами.
    public function surveys()
    {
        return $this->hasMany(Survey::class, 'author_id');
    }


    // Проверка, является ли пользователь автором.
    public function isAuthor()
    {
        return (int)$this->role === 1;
    }

    // роверка, является ли пользователь слушателем.
    public function isListener()
    {
        return (int)$this->role === 2;
    }
}
