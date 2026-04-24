<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portfolio_id')->constrained()->cascadeOnDelete();

            // Identity
            $table->string('title');
            $table->string('slug');
            $table->string('tagline')->nullable();          // pill label on the card
            $table->text('summary')->nullable();             // 1-2 line card description
            $table->longText('description')->nullable();     // long-form (markdown / html)

            // Meta
            $table->string('client')->nullable();
            $table->string('role')->nullable();
            $table->string('industry')->nullable();
            $table->string('location')->nullable();
            $table->date('started_at')->nullable();
            $table->date('completed_at')->nullable();
            $table->boolean('is_ongoing')->default(false);

            // Tech
            $table->json('tech_stack')->nullable();          // array of tech names
            $table->json('key_features')->nullable();        // array of bullets
            $table->json('challenges')->nullable();          // array of bullets

            // Links
            $table->string('live_url')->nullable();
            $table->string('repo_url')->nullable();
            $table->string('case_study_url')->nullable();
            $table->string('demo_credentials')->nullable();  // JSON or plain text creds for demo

            // SaaS / commerce
            $table->boolean('is_saas')->default(false);
            $table->string('saas_url')->nullable();
            $table->string('saas_pricing')->nullable();      // 'starts at $19/mo' etc.
            $table->boolean('is_for_sale')->default(false);
            $table->decimal('selling_price', 12, 2)->nullable();
            $table->string('selling_currency', 8)->default('USD');

            // Status / visibility
            $table->string('status', 32)->default('completed'); // completed, in_progress, planned
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('sort_order')->default(0);

            // SEO
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['portfolio_id', 'slug']);
            $table->index(['portfolio_id', 'is_published', 'sort_order']);
            $table->index(['portfolio_id', 'is_featured']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
