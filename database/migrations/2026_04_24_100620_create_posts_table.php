<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portfolio_id')->constrained()->cascadeOnDelete();
            $table->foreignId('blog_category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('title');
            $table->string('slug');
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();           // markdown / html
            $table->string('content_format', 16)->default('markdown'); // markdown | html | blocks
            $table->json('content_blocks')->nullable();        // optional structured content

            // Media
            $table->string('featured_image_path')->nullable();
            $table->string('featured_image_alt')->nullable();
            $table->json('gallery')->nullable();               // optional inline gallery paths

            // SEO
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->string('seo_og_image')->nullable();
            $table->string('canonical_url')->nullable();

            // Status
            $table->string('status', 16)->default('draft');    // draft | scheduled | published | archived
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('allow_comments')->default(false);

            // Stats
            $table->unsignedBigInteger('views_count')->default(0);
            $table->unsignedSmallInteger('reading_time_minutes')->nullable();

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['portfolio_id', 'slug']);
            $table->index(['portfolio_id', 'status', 'published_at']);
            $table->index(['portfolio_id', 'is_featured']);
            $table->index('blog_category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
