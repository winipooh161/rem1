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
            // Детализированный адрес объекта
            $table->string('city', 100)->nullable()->after('address');
            $table->string('street', 200)->nullable()->after('city');
            $table->string('house_number', 20)->nullable()->after('street');
            $table->string('entrance', 10)->nullable()->after('house_number');
            
            // Паспортные данные клиента
            $table->string('passport_series', 10)->nullable()->after('contact_phones');
            $table->string('passport_number', 20)->nullable()->after('passport_series');
            $table->string('passport_issued_by', 500)->nullable()->after('passport_number');
            $table->date('passport_issued_date')->nullable()->after('passport_issued_by');
            $table->string('passport_code', 20)->nullable()->after('passport_issued_date');
            
            // Адрес прописки клиента
            $table->string('registration_city', 100)->nullable()->after('passport_code');
            $table->string('registration_street', 200)->nullable()->after('registration_city');
            $table->string('registration_house', 20)->nullable()->after('registration_street');
            $table->string('registration_apartment', 10)->nullable()->after('registration_house');
            $table->string('registration_postal_code', 10)->nullable()->after('registration_apartment');
            
            // Дополнительные данные клиента
            $table->date('client_birth_date')->nullable()->after('registration_postal_code');
            $table->string('client_birth_place', 300)->nullable()->after('client_birth_date');
            $table->string('client_email', 100)->nullable()->after('client_birth_place');
            
            // Индексы для поиска
            $table->index(['city', 'street']);
            $table->index(['registration_city']);
            $table->index(['passport_series', 'passport_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex(['city', 'street']);
            $table->dropIndex(['registration_city']);
            $table->dropIndex(['passport_series', 'passport_number']);
            
            $table->dropColumn([
                'city',
                'street', 
                'house_number',
                'entrance',
                'passport_series',
                'passport_number',
                'passport_issued_by',
                'passport_issued_date',
                'passport_code',
                'registration_city',
                'registration_street',
                'registration_house',
                'registration_apartment',
                'registration_postal_code',
                'client_birth_date',
                'client_birth_place',
                'client_email'
            ]);
        });
    }
};
