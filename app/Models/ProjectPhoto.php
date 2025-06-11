<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectPhoto extends Model
{
    use HasFactory;

    /**
     * Атрибуты, которые можно массово присваивать.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'project_id',
        'filename',
        'original_name',
        'category',
        'comment',
        'size',
        'mime_type'
    ];

    /**
     * Получить проект, к которому принадлежит фотография.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Получить URL для просмотра фотографии.
     *
     * @return string
     */
    public function getPhotoUrlAttribute(): string
    {
        return asset('storage/project_photos/' . $this->project_id . '/' . $this->filename);
    }
}
