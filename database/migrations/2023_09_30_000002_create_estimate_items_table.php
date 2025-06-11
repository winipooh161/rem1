<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('estimate_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estimate_id')->constrained()->cascadeOnDelete();
            $table->string('position_number')->nullable(); // Номер позиции (№)
            $table->string('name'); // Название позиции
            $table->string('unit')->nullable(); // Единица измерения
            $table->decimal('quantity', 15, 3)->default(0); // Количество
            $table->decimal('price', 15, 2)->default(0); // Цена
            $table->decimal('cost', 15, 2)->default(0); // Стоимость (quantity * price)
            $table->decimal('markup_percent', 8, 2)->default(0); // Наценка в процентах
            $table->decimal('discount_percent', 8, 2)->default(0); // Скидка в процентах
            $table->decimal('client_price', 15, 2)->default(0); // Цена для заказчика
            $table->decimal('client_cost', 15, 2)->default(0); // Стоимость для заказчика
            $table->integer('position')->default(0); // Порядок сортировки
            $table->boolean('is_section_header')->default(false); // Признак заголовка раздела
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estimate_items');
    }
};
