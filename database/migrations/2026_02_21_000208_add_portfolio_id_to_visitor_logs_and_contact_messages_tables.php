<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visitor_logs', function (Blueprint $table) {
            $table->foreignId('portfolio_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        Schema::table('contact_messages', function (Blueprint $table) {
            $table->foreignId('portfolio_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('visitor_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('portfolio_id');
        });

        Schema::table('contact_messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('portfolio_id');
        });
    }
};
