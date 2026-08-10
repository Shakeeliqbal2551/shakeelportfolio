<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('role')->default('client')->after('external_link');
            $table->string('company_name')->nullable()->after('role');
            $table->string('your_title')->nullable()->after('company_name');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['role', 'company_name', 'your_title']);
        });
    }
};
