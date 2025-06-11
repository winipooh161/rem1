<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstimateItem extends Model
{
    use HasFactory;

    /**
     * Атрибуты, которые можно массово присваивать.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'estimate_id',
        'position',
        'position_number',
        'name',
        'unit',
        'quantity',
        'price',
        'cost',
        'markup_percent',
        'discount_percent',
        'client_price',
        'client_cost',
        'is_section_header',
    ];

    /**
     * Атрибуты, которые должны быть преобразованы.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'decimal:3',
        'price' => 'decimal:2',
        'cost' => 'decimal:2',
        'markup_percent' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'client_price' => 'decimal:2',
        'client_cost' => 'decimal:2',
        'is_section_header' => 'boolean',
    ];

    /**
     * Получить смету, к которой принадлежит элемент.
     */
    public function estimate(): BelongsTo
    {
        return $this->belongsTo(Estimate::class);
    }
    
    /**
     * Форматированная сумма элемента для отображения.
     *
     * @return string
     */
    public function formattedClientCost(): string
    {
        return number_format($this->client_cost, 2, ',', ' ') . ' ₽';
    }
    
    /**
     * Форматированная цена для отображения.
     *
     * @return string
     */
    public function formattedPrice(): string
    {
        return number_format($this->price, 2, ',', ' ') . ' ₽';
    }
    
    /**
     * Форматированное количество для отображения.
     *
     * @return string
     */
    public function formattedQuantity(): string
    {
        return number_format($this->quantity, 3, ',', ' ');
    }
}
