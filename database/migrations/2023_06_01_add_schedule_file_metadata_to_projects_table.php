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
        Schema::table('projects', function (Blueprint $table) {
            // Добавляем поля с метаданными для файла графика
            if (!Schema::hasColumn('projects', 'schedule_file_name')) {
                $table->string('schedule_file_name')->nullable()->after('schedule_file');
            }
            
            if (!Schema::hasColumn('projects', 'schedule_file_size')) {
                $table->unsignedBigInteger('schedule_file_size')->nullable()->after('schedule_file_name');
            }
            
            if (!Schema::hasColumn('projects', 'schedule_file_updated_at')) {
                $table->timestamp('schedule_file_updated_at')->nullable()->after('schedule_file_size');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'schedule_file_name',
                'schedule_file_size',
                'schedule_file_updated_at',
            ]);
        });
    }
};
