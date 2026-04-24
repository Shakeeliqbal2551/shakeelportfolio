<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
            $table->string('avatar_path')->nullable()->after('email');
            $table->boolean('is_admin')->default(false)->after('avatar_path');
            $table->string('locale', 8)->default('en')->after('is_admin');
            $table->string('timezone', 64)->default('UTC')->after('locale');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'avatar_path', 'is_admin', 'locale', 'timezone']);
        });
    }
};
