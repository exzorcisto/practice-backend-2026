<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    /**
     * @var string
     */
    protected $table = 'role';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array
     */
    protected $fillable = [
        'name_role',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role', 'id');
    }
}
