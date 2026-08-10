<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('about_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portfolio_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('heading')->nullable();
            $table->string('title')->nullable();
            $table->text('bio')->nullable();
            $table->json('info_cards')->nullable();
            // info_cards: array of {label, value, subvalue?} objects
            $table->string('cta_heading')->nullable();
            $table->string('cta_text')->nullable();
            $table->string('resume_path')->nullable();
            $table->string('profile_image_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('about_sections');
    }
};
