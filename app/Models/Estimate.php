<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Estimate extends Model
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
        'type', // main, additional, materials
        'status', // draft, pending, approved
        'description',
        'user_id',
        'total_amount',
        'file_path', // Добавлено поле для хранения пути к файлу
        'file_updated_at',
        'file_size',
        'file_name',
    ];

    /**
     * Атрибуты, которые должны быть преобразованы.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'total_amount' => 'decimal:2',
        'file_updated_at' => 'datetime',
    ];

    /**
     * Получить проект, к которому принадлежит смета.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Получить пользователя, создавшего смету.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Получить элементы сметы.
     */
    public function items(): HasMany
    {
        return $this->hasMany(EstimateItem::class);
    }
    
    /**
     * Получить путь к файлу сметы
     *
     * @return string
     */
    public function getFilePathAttribute(): string
    {
        // Если путь к файлу сохранен в БД, используем его
        if (!empty($this->attributes['file_path'])) {
            return $this->attributes['file_path'];
        }
        
        // Иначе генерируем стандартный путь
        return "estimates/{$this->project_id}/{$this->id}.xlsx";
    }
    
    /**
     * Получить URL для скачивания файла сметы
     *
     * @return string|null
     */
    public function getFileUrlAttribute(): ?string
    {
        $path = $this->file_path;
        
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path);
        }
        
        return null;
    }
    
    /**
     * Получить форматированный размер файла
     *
     * @return string|null
     */
    public function getFileSizeFormattedAttribute(): ?string
    {
        if (!$this->file_size) {
            return null;
        }
        
        $size = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $i = 0;
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }
        
        return round($size, 2) . ' ' . $units[$i];
    }
    
    /**
     * Получить строковое представление типа сметы
     *
     * @return string
     */
    public function getTypeTextAttribute(): string
    {
        $types = [
            'main' => 'Основная смета',
            'additional' => 'Дополнительная смета',
            'materials' => 'Смета по материалам',
        ];
        
        return $types[$this->type] ?? $this->type;
    }
    
    /**
     * Получить строковое представление статуса сметы
     *
     * @return string
     */
    public function getStatusTextAttribute(): string
    {
        $statuses = [
            'draft' => 'Черновик',
            'pending' => 'На согласовании',
            'approved' => 'Утверждена',
            'rejected' => 'Отклонена',
        ];
        
        return $statuses[$this->status] ?? $this->status;
    }
    
    /**
     * Статус сметы для отображения.
     *
     * @return string
     */
    public function statusBadge(): string
    {
        switch ($this->status) {
            case 'draft':
                return '<span class="badge bg-secondary">Черновик</span>';
            case 'pending':
                return '<span class="badge bg-warning text-dark">На рассмотрении</span>';
            case 'approved':
                return '<span class="badge bg-success">Утверждена</span>';
            case 'rejected':
                return '<span class="badge bg-danger">Отклонена</span>';
            default:
                return '<span class="badge bg-info">Неизвестно</span>';
        }
    }
    
    /**
     * Тип сметы для отображения.
     *
     * @return string
     */
    public function typeBadge(): string
    {
        switch ($this->type) {
            case 'main':
                return '<span class="badge bg-primary">Основная</span>';
            case 'additional':
                return '<span class="badge bg-info">Дополнительная</span>';
            case 'materials':
                return '<span class="badge bg-dark">Материалы</span>';
            default:
                return '<span class="badge bg-secondary">Неизвестно</span>';
        }
    }
    
    /**
     * Проверить, доступен ли файл сметы для скачивания.
     *
     * @return bool
     */
    public function hasFile(): bool
    {
        return $this->file_path && Storage::disk('public')->exists($this->file_path);
    }
    
    /**
     * URL для скачивания файла сметы.
     *
     * @return string|null
     */
    public function downloadUrl(): ?string
    {
        return $this->hasFile() ? route('partner.estimates.export', $this->id) : null;
    }
}
