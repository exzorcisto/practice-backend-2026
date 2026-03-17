<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    /**
     * Имя таблицы в БД (согласно твоей схеме)
     * @var string
     */
    protected $table = 'role';

    /**
     * Отключаем timestamps, так как в таблице role их нет
     * @var bool
     */
    public $timestamps = false;

    /**
     * Атрибуты для массового заполнения
     * @var array
     */
    protected $fillable = [
        'name_role',
    ];

    /**
     * Связь с пользователями.
     * Одна роль может принадлежать многим пользователям.
     */
    public function users(): HasMany
    {
        // Связываем по внешнему ключу 'role' в таблице users
        return $this->hasMany(User::class, 'role', 'id');
    }
}
