<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectScheduleItem extends Model
{
    use HasFactory;

    /**
     * Атрибуты, которые можно массово присваивать.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'project_id',
        'type',
        'name',
        'status',
        'start_date',
        'end_date',
        'duration',
        'notes',
        'position',
    ];

    /**
     * Атрибуты, которые должны быть преобразованы.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'duration' => 'integer',
        'position' => 'integer',
    ];

    /**
     * Получить проект, к которому принадлежит элемент графика.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
