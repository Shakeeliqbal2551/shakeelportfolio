<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('title');
        });

        DB::table('projects')->orderBy('id')->get(['id', 'title', 'portfolio_id'])->groupBy('portfolio_id')->each(function ($projects) {
            $used = [];

            foreach ($projects as $project) {
                $base = Str::slug($project->title);
                $slug = $base;
                $suffix = 2;

                while (in_array($slug, $used, true)) {
                    $slug = $base.'-'.$suffix;
                    $suffix++;
                }

                $used[] = $slug;

                DB::table('projects')->where('id', $project->id)->update(['slug' => $slug]);
            }
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change();
            $table->unique(['portfolio_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropUnique(['portfolio_id', 'slug']);
            $table->dropColumn('slug');
        });
    }
};
