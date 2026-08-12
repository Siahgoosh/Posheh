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
            $result = $publisher->publish($post);
            if ($result['ok'] ?? false) {
                $count++;
                $this->info("Published: {$post->slug}");
            } else {
                $this->warn("Blocked: {$post->slug} — ".implode('; ', $result['gate']['blockers'] ?? []));
            }
        }

        $this->info("Done. Published {$count} scheduled post(s).");

        return self::SUCCESS;
    }
}
