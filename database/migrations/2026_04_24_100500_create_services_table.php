<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portfolio_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->string('icon')->nullable();
            $table->string('icon_path')->nullable();
            $table->text('summary')->nullable();
            $table->longText('description')->nullable();
            $table->decimal('starting_price', 12, 2)->nullable();
            $table->string('currency', 8)->default('USD');
            $table->string('billing_cycle', 32)->nullable();      // hourly, project, monthly
            $table->json('included_features')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['portfolio_id', 'slug']);
            $table->index(['portfolio_id', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
