<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->foreignId('portfolio_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->boolean('is_read')->default(false)->after('message');
            $table->boolean('is_archived')->default(false)->after('is_read');
            $table->softDeletes();

            $table->index(['portfolio_id', 'is_read', 'created_at']);
        });

        Schema::table('visitor_logs', function (Blueprint $table) {
            $table->foreignId('portfolio_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('referrer')->nullable()->after('page_visited');

            $table->index(['portfolio_id', 'visit_time']);
        });
    }

    public function down(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->dropForeign(['portfolio_id']);
            $table->dropColumn(['portfolio_id', 'is_read', 'is_archived', 'deleted_at']);
        });

        Schema::table('visitor_logs', function (Blueprint $table) {
            $table->dropForeign(['portfolio_id']);
            $table->dropColumn(['portfolio_id', 'referrer']);
        });
    }
};
