<?php

namespace App\Modules\VirtualTour\Domain;

enum SceneType: string
{
    case Equirectangular = 'equirectangular';
    case FlatImage = 'flat_image';

    public function isFlat(): bool
    {
        return $this === self::FlatImage;
    }
}
