<?php

namespace App\Contracts\Ai;

/**
 * AI provider abstraction — never hard-code a single vendor in Content OS.
 */
interface AiProviderInterface
{
    public function key(): string;

    public function isAvailable(): bool;

    /**
     * @param  array{system?:string,user:string,temperature?:float,max_tokens?:int,model?:string,timeout?:int}  $request
     * @return array{text:string,prompt_tokens:int,completion_tokens:int,model:string,raw?:mixed,error?:string}
     */
    public function complete(array $request): array;
}
