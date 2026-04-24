<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portfolio_id')->constrained()->cascadeOnDelete();
            $table->text('quote');
            $table->string('author');
            $table->string('role')->nullable();
            $table->string('company')->nullable();
            $table->string('country')->nullable();
            $table->string('avatar_path')->nullable();
            $table->string('source_url')->nullable();              // LinkedIn / Upwork link to verify
            $table->unsignedTinyInteger('rating')->nullable();     // 1-5
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['portfolio_id', 'is_visible', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testimonials');
    }
};
