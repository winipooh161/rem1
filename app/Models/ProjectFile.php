<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectFile extends Model
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
        'file_type',
        'size',
        'mime_type',
        'description',
    ];

    /**
     * Получить проект, к которому принадлежит файл.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Получить ссылку для скачивания файла.
     *
     * @return string
     */
    public function getDownloadUrlAttribute(): string
    {
        return route('partner.project-files.download', $this->id);
    }

    /**
     * Получить расширение файла.
     *
     * @return string
     */
    public function getExtensionAttribute(): string
    {
        return pathinfo($this->original_name, PATHINFO_EXTENSION);
    }

    /**
     * Определить, является ли файл изображением.
     *
     * @return bool
     */
    public function getIsImageAttribute(): bool
    {
        return in_array($this->extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp']);
    }

    /**
     * Получить иконку для файла на основе его типа.
     *
     * @return string
     */
    public function getFileIconAttribute(): string
    {
        if ($this->is_image) {
            return 'fas fa-image';
        }
        
        $iconMap = [
            'pdf' => 'fas fa-file-pdf',
            'doc' => 'fas fa-file-word',
            'docx' => 'fas fa-file-word',
            'xls' => 'fas fa-file-excel',
            'xlsx' => 'fas fa-file-excel',
            'ppt' => 'fas fa-file-powerpoint',
            'pptx' => 'fas fa-file-powerpoint',
            'txt' => 'fas fa-file-alt',
            'zip' => 'fas fa-file-archive',
            'rar' => 'fas fa-file-archive',
            'dwg' => 'fas fa-drafting-compass',
            'dxf' => 'fas fa-drafting-compass',
        ];
        
        return $iconMap[$this->extension] ?? 'fas fa-file';
    }
}
