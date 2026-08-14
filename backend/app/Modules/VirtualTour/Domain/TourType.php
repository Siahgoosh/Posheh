<?php

namespace App\Modules\VirtualTour\Domain;

enum TourType: string
{
    case Panorama360 = 'panorama_360';
    case SmartWalk = 'smart_walk';

    public function defaultSceneType(): SceneType
    {
        return match ($this) {
            self::Panorama360 => SceneType::Equirectangular,
            self::SmartWalk => SceneType::FlatImage,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Panorama360 => '360 Tour',
            self::SmartWalk => 'Smart Walk',
        };
    }
}
