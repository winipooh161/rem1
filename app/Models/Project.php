<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes;
    
    /**
     * Атрибуты, которые можно массово присваивать.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'partner_id',
        'estimator_id',
        'client_name',
        'client_phone',
        'client_email',
        'address',
        // Детализированный адрес объекта
        'city',
        'street',
        'house_number',
        'entrance',
        'apartment_number',
        'area',
        'phone',
        'object_type',
        'work_type',
        'status',
        'contract_date',
        'contract_number',
        'work_start_date',
        'work_amount',
        'materials_amount',
        'camera_link',
        'code_inserted',
        'contact_phones',
        // Паспортные данные клиента
        'passport_series',
        'passport_number',
        'passport_issued_by',
        'passport_issued_date',
        'passport_code',
        // Адрес прописки клиента
        'registration_city',
        'registration_street',
        'registration_house',
        'registration_apartment',
        'registration_postal_code',
        // Дополнительные данные клиента
        'client_birth_date',
        'client_birth_place',
        'client_email',
        'description',
        'branch',
        'user_id',
    ];

    /**
     * Атрибуты, которые должны быть преобразованы.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'contract_date' => 'date',
        'work_start_date' => 'date',
        'passport_issued_date' => 'date',
        'client_birth_date' => 'date',
        'work_amount' => 'decimal:2',
        'materials_amount' => 'decimal:2',
        'code_inserted' => 'boolean',
    ];

    /**
     * Получить партнера, которому принадлежит объект.
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'partner_id');
    }
    
    /**
     * Получить сметы объекта.
     */
    public function estimates(): HasMany
    {
        return $this->hasMany(Estimate::class);
    }

    /**
     * Получить файлы, связанные с проектом.
     */
    public function files(): HasMany
    {
        return $this->hasMany(ProjectFile::class);
    }

    /**
     * Получить файлы дизайн-проекта.
     */
    public function designFiles(): HasMany
    {
        return $this->hasMany(ProjectFile::class)->where('file_type', 'design');
    }

    /**
     * Получить файлы схем.
     */
    public function schemeFiles(): HasMany
    {
        return $this->hasMany(ProjectFile::class)->where('file_type', 'scheme');
    }

    /**
     * Получить файлы документов.
     */
    public function documentFiles(): HasMany
    {
        return $this->hasMany(ProjectFile::class)->where('file_type', 'document');
    }

    /**
     * Получить файлы договоров.
     */
    public function contractFiles(): HasMany
    {
        return $this->hasMany(ProjectFile::class)->where('file_type', 'contract');
    }

    /**
     * Получить прочие файлы.
     */
    public function otherFiles(): HasMany
    {
        return $this->hasMany(ProjectFile::class)->where('file_type', 'other');
    }

    /**
     * Получить элементы графика работ и материалов проекта.
     */
    public function financeItems(): HasMany
    {
        return $this->hasMany(ProjectFinanceItem::class);
    }

    /**
     * Получить основные работы.
     */
    public function mainWorks(): HasMany
    {
        return $this->hasMany(ProjectFinanceItem::class)->where('type', 'main_work')->orderBy('position');
    }

    /**
     * Получить основные материалы.
     */
    public function mainMaterials(): HasMany
    {
        return $this->hasMany(ProjectFinanceItem::class)->where('type', 'main_material')->orderBy('position');
    }

    /**
     * Получить дополнительные работы.
     */
    public function additionalWorks(): HasMany
    {
        return $this->hasMany(ProjectFinanceItem::class)->where('type', 'additional_work')->orderBy('position');
    }

    /**
     * Получить дополнительные материалы.
     */
    public function additionalMaterials(): HasMany
    {
        return $this->hasMany(ProjectFinanceItem::class)->where('type', 'additional_material')->orderBy('position');
    }

    /**
     * Получить элементы транспортировки.
     */
    public function transportationItems(): HasMany
    {
        return $this->hasMany(ProjectFinanceItem::class)->where('type', 'transportation')->orderBy('position');
    }

    /**
     * Получить проверки, связанные с проектом.
     */
    public function checks()
    {
        return $this->hasMany(ProjectCheck::class);
    }

    /**
     * Получить фотографии, связанные с проектом.
     */
    public function photos(): HasMany
    {
        return $this->hasMany(ProjectPhoto::class);
    }

    /**
     * Получить фотографии проекта по категории.
     * 
     * @param string $category
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPhotosByCategory(string $category)
    {
        return $this->photos()->where('category', $category)->orderBy('created_at', 'desc')->get();
    }

    /**
     * Получить список уникальных категорий фотографий проекта.
     * 
     * @return \Illuminate\Support\Collection
     */
    public function getPhotoCategories()
    {
        return $this->photos()
            ->select('category')
            ->distinct()
            ->pluck('category')
            ->sort();
    }

    /**
     * Получить строковое представление типа работ.
     *
     * @return string
     */
    public function getWorkTypeTextAttribute(): string
    {
        $types = [
            'repair' => 'Ремонт',
            'design' => 'Дизайн',
            'construction' => 'Строительство',
        ];
        
        return $types[$this->work_type] ?? $this->work_type;
    }

    /**
     * Получить общую сумму проекта.
     *
     * @return float
     */
    public function getTotalAmountAttribute(): float
    {
        return $this->work_amount + $this->materials_amount;
    }

    /**
     * Статус объекта для отображения.
     *
     * @return string HTML-код бейджа статуса
     */
    public function statusBadge(): string
    {
        switch ($this->status) {
            case 'new':
                return '<span class="badge bg-primary">Новый</span>';
            case 'in_progress':
                return '<span class="badge bg-warning text-dark">В работе</span>';
            case 'on_hold':
                return '<span class="badge bg-secondary">Приостановлен</span>';
            case 'completed':
                return '<span class="badge bg-success">Завершен</span>';
            case 'cancelled':
                return '<span class="badge bg-danger">Отменен</span>';
            default:
                return '<span class="badge bg-info">Неизвестно</span>';
        }
    }
    
    /**
     * Полное наименование объекта (ФИО клиента + адрес).
     *
     * @return string
     */
    public function fullName(): string
    {
        return $this->client_name . ' (' . $this->address . ')';
    }

    /**
     * Получить назначенного сметчика проекта.
     */
    public function estimator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'estimator_id');
    }

    /**
     * Получить полный адрес объекта.
     *
     * @return string
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->city,
            $this->street,
            $this->house_number ? 'д. ' . $this->house_number : null,
            $this->apartment_number ? 'кв. ' . $this->apartment_number : null,
            $this->entrance ? 'подъезд ' . $this->entrance : null
        ]);
        
        return implode(', ', $parts) ?: $this->address;
    }

    /**
     * Получить полный адрес прописки клиента.
     *
     * @return string
     */
    public function getFullRegistrationAddressAttribute(): string
    {
        $parts = array_filter([
            $this->registration_postal_code,
            $this->registration_city,
            $this->registration_street,
            $this->registration_house ? 'д. ' . $this->registration_house : null,
            $this->registration_apartment ? 'кв. ' . $this->registration_apartment : null
        ]);
        
        return implode(', ', $parts);
    }

    /**
     * Получить полные паспортные данные.
     *
     * @return string
     */
    public function getFullPassportDataAttribute(): string
    {
        if (!$this->passport_series || !$this->passport_number) {
            return '';
        }
        
        $passport = $this->passport_series . ' ' . $this->passport_number;
        
        if ($this->passport_issued_by) {
            $passport .= ', выдан: ' . $this->passport_issued_by;
        }
        
        if ($this->passport_issued_date) {
            $passport .= ', дата выдачи: ' . $this->passport_issued_date->format('d.m.Y');
        }
        
        if ($this->passport_code) {
            $passport .= ', код подразделения: ' . $this->passport_code;
        }
        
        return $passport;
    }
}
