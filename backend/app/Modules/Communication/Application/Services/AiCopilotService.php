<?php

namespace App\Modules\Communication\Application\Services;

use App\Models\Communication\CommAiSuggestion;
use App\Models\Communication\CommAiSummary;
use App\Models\Communication\CommConversation;
use App\Models\Communication\CommKnowledgeArticle;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AiCopilotService
{
    public function __construct(private readonly CommunicationSettingsService $commSettings) {}

    /** @return list<string> */
    public function suggestReplies(CommConversation $conversation, int $count = 3): array
    {
        $messages = $conversation->messages()->orderByDesc('id')->limit(8)->get();
        $lastVisitor = $messages->firstWhere('sender_type', 'visitor');
        $context = $lastVisitor?->body ?? '';

        $suggestions = $this->generateSuggestions($context, $conversation);

        CommAiSuggestion::create([
            'conversation_id' => $conversation->id,
            'message_id' => $lastVisitor?->id,
            'suggestions' => $suggestions,
        ]);

        return $suggestions;
    }

    public function summarize(CommConversation $conversation): array
    {
        $lines = $conversation->messages()
            ->orderBy('created_at')
            ->limit(40)
            ->get()
            ->map(fn ($m) => ($m->sender_type === 'visitor' ? 'مشتری' : 'اپراتور').': '.$m->body)
            ->implode("\n");

        $category = $this->classifyCategory($lines);
        $sentiment = $this->analyzeSentiment($lines);
        $summary = $this->buildSummary($lines);

        CommAiSummary::create([
            'conversation_id' => $conversation->id,
            'summary' => $summary,
            'category' => $category,
            'sentiment' => $sentiment,
        ]);

        return [
            'summary' => $summary,
            'category' => $category,
            'sentiment' => $sentiment,
            'alert' => in_array($sentiment, ['angry', 'unhappy'], true),
        ];
    }

    /** @return list<array{title: string, slug: string}> */
    public function knowledgeMatches(string $query, ?int $officeId = null): array
    {
        return CommKnowledgeArticle::query()
            ->where('is_published', true)
            ->when($officeId, fn ($q) => $q->where(fn ($q2) => $q2->whereNull('office_id')->orWhere('office_id', $officeId)))
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', '%'.$query.'%')
                    ->orWhere('body', 'like', '%'.$query.'%');
            })
            ->limit(5)
            ->get(['title', 'slug'])
            ->all();
    }

    /** @return list<string> */
    private function generateSuggestions(string $context, CommConversation $conversation): array
    {
        $provider = $this->commSettings->aiProvider();

        if ($provider === 'openai' && $this->commSettings->aiOpenaiKey()) {
            try {
                $response = Http::withToken($this->commSettings->aiOpenaiKey())
                    ->timeout(20)
                    ->post('https://api.openai.com/v1/chat/completions', [
                        'model' => $this->commSettings->aiOpenaiModel(),
                        'messages' => [
                            ['role' => 'system', 'content' => 'You are a helpful Persian support agent for Posheh real estate SaaS. Reply with 3 short suggested responses in Persian, separated by |||'],
                            ['role' => 'user', 'content' => $context],
                        ],
                        'temperature' => 0.7,
                    ]);

                $text = $response->json('choices.0.message.content') ?? '';
                if ($text) {
                    return array_slice(array_filter(array_map('trim', explode('|||', $text))), 0, 3);
                }
            } catch (\Throwable) {
                // fallback internal
            }
        }

        $name = $conversation->lead?->first_name ?? 'دوست عزیز';
        return [
            "سلام {$name}، ممنون از پیامتون. همین الان بررسی می‌کنم.",
            'ممنون — برای راهنمایی بهتر، لطفاً شماره تماس یا نام دفتر رو هم بفرستید.',
            'درخواست شما ثبت شد؛ تا چند دقیقه دیگه جزئیات رو ارسال می‌کنم.',
        ];
    }

    private function buildSummary(string $lines): string
    {
        if (Str::length($lines) < 200) {
            return 'گفتگوی کوتاه — '.$Str::limit($lines, 180);
        }

        return Str::limit($lines, 400);
    }

    private function classifyCategory(string $text): string
    {
        $lower = Str::lower($text);
        if (Str::contains($lower, ['قیمت', 'پلن', 'خرید', 'اشتراک'])) {
            return 'sales';
        }
        if (Str::contains($lower, ['خطا', 'باگ', 'کار نمی'])) {
            return 'bug';
        }
        if (Str::contains($lower, ['مالی', 'فاکتور', 'پرداخت'])) {
            return 'finance';
        }
        if (Str::contains($lower, ['پیشنهاد', 'قابلیت'])) {
            return 'feature_request';
        }

        return 'support';
    }

    private function analyzeSentiment(string $text): string
    {
        $lower = Str::lower($text);
        if (Str::contains($lower, ['عصبانی', 'افتضاح', 'بد', 'ناراضی'])) {
            return 'angry';
        }
        if (Str::contains($lower, ['ممنون', 'عالی', 'خوب', 'راضی'])) {
            return 'happy';
        }

        return 'neutral';
    }
}
