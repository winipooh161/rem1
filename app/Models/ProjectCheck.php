<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectCheck extends Model
{
    use HasFactory;

    /**
     * Атрибуты, которые можно массово присваивать.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'project_id',
        'check_id',
        'category',
        'status',
        'comment',
        'user_id',
    ];

    /**
     * Атрибуты, которые должны быть преобразованы.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'boolean',
    ];

    /**
     * Получить проект, к которому относится проверка.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Получить пользователя, выполнившего проверку.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
