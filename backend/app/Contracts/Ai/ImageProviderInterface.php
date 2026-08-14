<?php

namespace App\Contracts\Ai;

interface ImageProviderInterface
{
    public function key(): string;

    public function isAvailable(): bool;

    /**
     * @param  array{prompt:string,negative_prompt?:string,model?:string,resolution?:string,quality?:string,timeout?:int}  $request
     * @return array{ok:bool,binary:?string,mime:?string,width:?int,height:?int,model:string,error?:string,cost_toman?:int}
     */
    public function generate(array $request): array;
}
