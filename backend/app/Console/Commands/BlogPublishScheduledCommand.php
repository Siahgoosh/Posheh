<?php

namespace App\Console\Commands;

use App\Models\BlogPost;
use App\Services\Blog\BlogPublishService;
use Illuminate\Console\Command;

class BlogPublishScheduledCommand extends Command
{
    protected $signature = 'blog:publish-scheduled';

    protected $description = 'Publish scheduled blog posts whose scheduled_at has passed';

    public function handle(BlogPublishService $publisher): int
    {
        $due = BlogPost::query()
            ->where('review_status', 'scheduled')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->get();

        $count = 0;
        foreach ($due as $post) {
            try {
                $result = $publisher->publish($post);
                if ($result['ok'] ?? false) {
                    $count++;
                    $this->info("Published: {$post->slug}");
                } else {
                    $blockers = array_merge(
                        $result['gate']['blockers'] ?? [],
                        $result['ops_blockers'] ?? []
                    );
                    $this->warn("Blocked: {$post->slug} — ".implode('; ', $blockers));
                }
            } catch (\Throwable $e) {
                $this->error("Failed: {$post->slug} — ".$e->getMessage());
            }
        }

        $this->info("Done. Published {$count} scheduled post(s).");

        return self::SUCCESS;
    }
}
