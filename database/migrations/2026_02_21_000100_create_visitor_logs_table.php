<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitor_logs', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 100)->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->string('region')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->string('page_visited')->default('home');
            $table->text('user_agent')->nullable();
            $table->string('browser')->nullable();
            $table->string('os')->nullable();
            $table->string('device_type')->nullable();
            $table->string('screen_resolution')->nullable();
            $table->boolean('is_repeat_visitor')->default(false);
            $table->dateTime('visit_time')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitor_logs');
    }
};

