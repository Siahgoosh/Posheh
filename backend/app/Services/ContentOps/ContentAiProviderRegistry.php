<?php

namespace App\Services\ContentOps;

use App\Contracts\Ai\AiProviderInterface;
use App\Services\Ai\Providers\LocalHeuristicAiProvider;
use App\Services\Ai\Providers\MockAiProvider;
use App\Services\Ai\Providers\OpenAiProvider;
use App\Services\Blog\BlogAiAssistantService;

class ContentAiProviderRegistry
{
    /** @var array<string, AiProviderInterface> */
    private array $providers = [];

    public function __construct(
        private readonly BlogAiAssistantService $assistant,
    ) {
        $this->providers['mock'] = new MockAiProvider;
        $this->providers['local'] = new LocalHeuristicAiProvider($this->assistant);
        $this->providers['openai'] = new OpenAiProvider;
    }

    public function get(?string $key = null): AiProviderInterface
    {
        $key = $key ?: (string) config('content_ops.default_provider', 'local');
        $provider = $this->providers[$key] ?? null;
        if (! $provider || ! $provider->isAvailable()) {
            return $this->providers['local'];
        }

        return $provider;
    }

    /** @return list<array{key:string,available:bool}> */
    public function list(): array
    {
        $out = [];
        foreach ($this->providers as $key => $p) {
            $out[] = ['key' => $key, 'available' => $p->isAvailable()];
        }

        return $out;
    }
}
