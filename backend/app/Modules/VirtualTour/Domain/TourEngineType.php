<?php

namespace App\Modules\VirtualTour\Domain;

/**
 * Registry of virtual tour rendering engines — extensible for future technologies.
 */
enum TourEngineType: string
{
    case Panorama360 = 'panorama_360';
    case SmartWalk = 'smart_walk';
    case Matterport = 'matterport';
    case LiDAR = 'lidar';
    case Mesh3D = 'mesh_3d';
    case GaussianSplat = 'gaussian_splat';
    case NeRF = 'nerf';
    case Photogrammetry = 'photogrammetry';
    case Drone = 'drone';
    case FloorPlan3D = 'floor_plan_3d';
    case DigitalTwin = 'digital_twin';

    public function isImplemented(): bool
    {
        return match ($this) {
            self::Panorama360, self::SmartWalk => true,
            default => false,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Panorama360 => '360° Equirectangular',
            self::SmartWalk => 'Smart Walk',
            self::Matterport => 'Matterport',
            self::LiDAR => 'LiDAR Scan',
            self::Mesh3D => '3D Mesh',
            self::GaussianSplat => 'Gaussian Splatting',
            self::NeRF => 'NeRF',
            self::Photogrammetry => 'Photogrammetry',
            self::Drone => 'Drone Tour',
            self::FloorPlan3D => '3D Floor Plan',
            self::DigitalTwin => 'Digital Twin',
        };
    }

    /** @return list<string> */
    public static function implementedValues(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $e) => $e->isImplemented()
        ));
    }
}
