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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained('users')->onDelete('cascade');
            $table->string('client_name');
            $table->string('address');
            $table->string('apartment_number')->nullable();
            $table->decimal('area', 8, 2)->nullable();
            $table->string('phone');
            $table->string('object_type')->nullable();
            $table->enum('work_type', ['repair', 'design', 'construction'])->default('repair');
            $table->date('contract_date')->nullable();
            $table->string('contract_number')->nullable();
            $table->date('work_start_date')->nullable();
            $table->decimal('work_amount', 10, 2)->default(0);
            $table->decimal('materials_amount', 10, 2)->default(0);
            $table->string('camera_link')->nullable();
            $table->string('schedule_link')->nullable();
            $table->boolean('code_inserted')->default(false);
            $table->text('contact_phones')->nullable();
            $table->string('branch')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
