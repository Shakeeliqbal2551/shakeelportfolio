<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('blog_post_tag', function (Blueprint $table) {
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('blog_tag_id')->constrained()->cascadeOnDelete();

            $table->primary(['post_id', 'blog_tag_id'], 'post_tag_pk');
            $table->index('blog_tag_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_post_tag');
    }
};
