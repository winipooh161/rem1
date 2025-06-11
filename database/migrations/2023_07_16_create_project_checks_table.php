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
        Schema::create('project_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->integer('check_id'); // ID проверки из списка
            $table->string('category'); // Категория проверки
            $table->boolean('status')->default(false); // Статус: false - не проверено, true - проверено
            $table->text('comment')->nullable(); // Комментарий к проверке
            $table->foreignId('user_id')->nullable()->constrained(); // Кто выполнил проверку
            $table->timestamps();
            
            // Индекс для быстрого поиска по проекту и ID проверки
            $table->unique(['project_id', 'check_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_checks');
    }
};
