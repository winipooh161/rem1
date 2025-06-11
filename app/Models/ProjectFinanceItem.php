<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectFinanceItem extends Model
{
    use HasFactory;

    /**
     * Атрибуты, которые можно массово присваивать.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'project_id',
        'name',
        'type',
        'total_amount',
        'paid_amount',
        'payment_date',
        'position',
        // Удаляем 'user_id' из списка разрешенных полей
    ];

    /**
     * Атрибуты, которые должны быть преобразованы.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'payment_date' => 'date',
        'position' => 'integer',
    ];

    /**
     * Получить проект, к которому относится этот финансовый элемент.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Получить пользователя, создавшего этот финансовый элемент.
     * Оставляем метод отношения, но он не будет работать без колонки.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
