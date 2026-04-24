<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portfolio_id')->unique()->constrained()->cascadeOnDelete();

            // Hero
            $table->string('hero_badge')->nullable();
            $table->string('hero_subtitle')->nullable();
            $table->text('hero_title_html')->nullable();
            $table->text('hero_description')->nullable();
            $table->string('hero_cta_primary_label')->nullable();
            $table->string('hero_cta_primary_url')->nullable();
            $table->string('hero_cta_secondary_label')->nullable();
            $table->string('hero_cta_secondary_url')->nullable();
            $table->json('hero_reassurance')->nullable();
            $table->json('hero_flags')->nullable();

            // Stats
            $table->string('stat_years')->nullable();
            $table->string('stat_projects')->nullable();
            $table->string('stat_clients')->nullable();
            $table->string('stat_countries')->nullable();

            // About
            $table->string('about_subtitle')->nullable();
            $table->string('about_title')->nullable();
            $table->text('about_description')->nullable();
            $table->string('about_location')->nullable();
            $table->string('about_phone')->nullable();
            $table->string('about_email')->nullable();
            $table->string('about_whatsapp')->nullable();
            $table->string('about_linkedin')->nullable();
            $table->string('about_resume_path')->nullable();

            // Contact
            $table->string('contact_subtitle')->nullable();
            $table->string('contact_title')->nullable();
            $table->text('contact_description')->nullable();
            $table->string('contact_address')->nullable();

            // SEO / OG
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->text('seo_keywords')->nullable();
            $table->string('seo_og_image')->nullable();
            $table->string('canonical_url')->nullable();

            // Theme overrides (raw JSON for forward-compat with multi-themes)
            $table->json('theme_options')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
