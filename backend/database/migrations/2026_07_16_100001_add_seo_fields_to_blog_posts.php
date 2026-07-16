<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->string('category_slug')->nullable()->after('slug');
            $table->string('category_label')->nullable()->after('category_slug');
            $table->string('pillar_slug')->nullable()->after('category_label');
            $table->json('faq')->nullable()->after('keywords');
            $table->json('related_slugs')->nullable()->after('faq');
            $table->string('cta_text')->nullable()->after('related_slugs');
            $table->string('cta_url')->nullable()->after('cta_text');
        });
    }

    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropColumn([
                'category_slug', 'category_label', 'pillar_slug',
                'faq', 'related_slugs', 'cta_text', 'cta_url',
            ]);
        });
    }
};
