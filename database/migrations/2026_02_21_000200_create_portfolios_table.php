<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portfolios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->string('theme')->default('default');
            $table->string('site_title');
            $table->text('meta_description')->nullable();

            // Hero section
            $table->string('hero_badge_text')->nullable();
            $table->string('hero_subtitle')->nullable();
            $table->string('hero_title')->nullable();
            $table->string('hero_title_accent')->nullable();
            $table->text('hero_description')->nullable();
            $table->string('hero_cta_primary_label')->nullable();
            $table->string('hero_cta_primary_href')->nullable();
            $table->string('hero_cta_secondary_label')->nullable();
            $table->string('hero_cta_secondary_href')->nullable();
            $table->json('hero_reassurance_items')->nullable();
            $table->json('hero_stats')->nullable();
            $table->string('trust_label')->nullable();
            $table->string('trust_flags')->nullable();

            $table->string('contact_email')->nullable();
            $table->string('whatsapp_number')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolios');
    }
};
