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
        Schema::table('visitor_logs', function (Blueprint $table) {
            $table->unsignedInteger('duration_seconds')->nullable()->after('visit_time');
            $table->string('session_token')->nullable()->after('duration_seconds');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visitor_logs', function (Blueprint $table) {
            $table->dropColumn(['duration_seconds', 'session_token']);
        });
    }
};
