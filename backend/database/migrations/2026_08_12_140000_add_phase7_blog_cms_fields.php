<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            if (! Schema::hasColumn('blog_posts', 'content_type')) {
                $table->string('content_type', 40)->nullable()->after('funnel_stage');
            }
            if (! Schema::hasColumn('blog_posts', 'schema_type')) {
                $table->string('schema_type', 40)->nullable()->after('content_type');
            }
            if (! Schema::hasColumn('blog_posts', 'sources')) {
                $table->json('sources')->nullable()->after('content_brief');
            }
            if (! Schema::hasColumn('blog_posts', 'last_reviewed_at')) {
                $table->timestamp('last_reviewed_at')->nullable()->after('content_updated_at');
            }
            if (! Schema::hasColumn('blog_posts', 'edit_locked_by')) {
                $table->unsignedBigInteger('edit_locked_by')->nullable()->after('rebuild_locked');
            }
            if (! Schema::hasColumn('blog_posts', 'edit_locked_at')) {
                $table->timestamp('edit_locked_at')->nullable()->after('edit_locked_by');
            }
            if (! Schema::hasColumn('blog_posts', 'autosave_payload')) {
                $table->json('autosave_payload')->nullable()->after('quality_scores');
            }
            if (! Schema::hasColumn('blog_posts', 'word_count')) {
                $table->unsignedInteger('word_count')->nullable()->after('reading_time');
            }
        });
    }

    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            foreach ([
                'content_type', 'schema_type', 'sources', 'last_reviewed_at',
                'edit_locked_by', 'edit_locked_at', 'autosave_payload', 'word_count',
            ] as $col) {
                if (Schema::hasColumn('blog_posts', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
