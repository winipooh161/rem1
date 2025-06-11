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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'partner_id')) {
                $table->unsignedBigInteger('partner_id')->nullable()->after('id');
                $table->foreign('partner_id')->references('id')->on('users')->onDelete('set null');
                $table->index('partner_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'partner_id')) {
                $table->dropForeign(['partner_id']);
                $table->dropColumn('partner_id');
            }
        });
    }
};
