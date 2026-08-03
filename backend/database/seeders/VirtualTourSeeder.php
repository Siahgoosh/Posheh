<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Office;
use App\Models\Property;
use App\Models\User;
use App\Models\VirtualTour;
use App\Models\VirtualTourHotspot;
use Illuminate\Database\Seeder;

class VirtualTourSeeder extends Seeder
{
    public function run(): void
    {
        [$office, $user] = $this->ensureDemoContext();
        $property = Property::where('office_id', $office->id)->first();

        $tour = VirtualTour::updateOrCreate(
            ['slug' => 'demo-apartment-pasdaran'],
            [
                'office_id' => $office->id,
                'property_id' => $property?->id,
                'created_by' => $user->id,
                'title' => 'تور مجازی آپارتمان نمونه — پاسداران',
                'description' => 'نمونه تور مجازی ۳۶۰ درجه مشابه ۳۶۰نما — بازدید آنلاین از پذیرایی، آشپزخانه و اتاق خواب',
                'status' => 'published',
                'published_at' => now(),
                'settings' => [
                    'brand_color' => '#6366f1',
                    'phone' => '09120000000',
                    'whatsapp' => '09120000000',
                    'show_contact_form' => true,
                    'show_gallery' => true,
                    'show_floor_plan' => true,
                    'enable_vr' => true,
                    'enable_gyroscope' => true,
                    'map_lat' => 35.7575,
                    'map_lng' => 51.4560,
                ],
            ]
        );

        $tour->scenes()->delete();

        $living = $tour->scenes()->create([
            'name' => 'پذیرایی',
            'status' => 'published',
            'is_default' => true,
            'is_visible' => true,
            'panorama_path' => 'demo/sphere.jpg',
            'default_yaw' => 0,
            'sort_order' => 0,
            'floor_plan_x' => 30,
            'floor_plan_y' => 40,
        ]);

        $kitchen = $tour->scenes()->create([
            'name' => 'آشپزخانه',
            'status' => 'published',
            'is_default' => false,
            'is_visible' => true,
            'panorama_path' => 'demo/sphere-small.jpg',
            'default_yaw' => 90,
            'sort_order' => 1,
            'floor_plan_x' => 60,
            'floor_plan_y' => 35,
        ]);

        $bedroom = $tour->scenes()->create([
            'name' => 'اتاق خواب',
            'status' => 'published',
            'is_default' => false,
            'is_visible' => true,
            'panorama_path' => 'demo/sphere.jpg',
            'default_yaw' => 180,
            'sort_order' => 2,
            'floor_plan_x' => 45,
            'floor_plan_y' => 70,
        ]);

        VirtualTourHotspot::create([
            'scene_id' => $living->id,
            'type' => 'scene',
            'target_scene_id' => $kitchen->id,
            'yaw' => 45,
            'pitch' => 0,
            'title' => 'رفتن به آشپزخانه',
            'icon' => 'arrow',
        ]);

        VirtualTourHotspot::create([
            'scene_id' => $living->id,
            'type' => 'info',
            'yaw' => -30,
            'pitch' => 10,
            'title' => 'نورگیری عالی',
            'content' => 'پنجره‌های دوجداره با نمای جنوبی',
            'icon' => 'info',
        ]);

        VirtualTourHotspot::create([
            'scene_id' => $kitchen->id,
            'type' => 'scene',
            'target_scene_id' => $bedroom->id,
            'yaw' => 120,
            'pitch' => -5,
            'title' => 'اتاق خواب',
            'icon' => 'arrow',
        ]);

        VirtualTourHotspot::create([
            'scene_id' => $bedroom->id,
            'type' => 'scene',
            'target_scene_id' => $living->id,
            'yaw' => 0,
            'pitch' => 0,
            'title' => 'بازگشت به پذیرایی',
            'icon' => 'arrow',
        ]);
    }

    /** @return array{0: Office, 1: User} */
    private function ensureDemoContext(): array
    {
        $office = Office::where('slug', 'demo-office')->first() ?? Office::first();

        if (! $office) {
            $office = Office::create([
                'slug' => 'demo-office',
                'name' => 'دفتر املاک نمونه',
                'phone' => '02112345678',
                'city' => 'تهران',
                'address' => 'خیابان ولیعصر',
                'is_active' => true,
            ]);
        }

        $user = User::where('office_id', $office->id)->first();

        if (! $user) {
            $user = User::updateOrCreate(
                ['mobile' => '09129999998'],
                [
                    'name' => 'نماینده تور مجازی',
                    'office_id' => $office->id,
                    'role' => UserRole::OfficeManager,
                    'is_active' => true,
                    'mobile_verified_at' => now(),
                ]
            );
        }

        return [$office, $user];
    }
}
