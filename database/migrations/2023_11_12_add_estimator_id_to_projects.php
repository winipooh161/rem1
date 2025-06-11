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
            if (!Schema::hasColumn('projects', 'estimator_id')) {
                $table->unsignedBigInteger('estimator_id')->nullable()->after('partner_id');
                $table->foreign('estimator_id')->references('id')->on('users')->onDelete('set null');
                $table->index('estimator_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'estimator_id')) {
                $table->dropForeign(['estimator_id']);
                $table->dropColumn('estimator_id');
            }
        });
    }
};
