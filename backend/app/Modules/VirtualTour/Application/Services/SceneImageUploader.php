<?php

namespace App\Modules\VirtualTour\Application\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class SceneImageUploader
{
    public function __construct(
        private readonly TourManager $tourManager,
        private readonly SceneManager $sceneManager,
        private readonly ImageVariantService $imageVariants,
    ) {}

    /** @return string[] */
    public function allowedMimes(): array
    {
        return config('virtual-tour.allowed_scene_image_mimes', [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/x-png',
            'image/webp',
            'image/avif',
        ]);
    }

    public function maxSizeBytes(): int
    {
        return (int) config('virtual-tour.max_scene_image_size_mb', 50) * 1024 * 1024;
    }

    /**
     * @return array{valid: bool, errors: string[]}
     */
    public function validate(UploadedFile $file): array
    {
        $errors = [];

        if (! in_array($file->getMimeType(), $this->allowedMimes(), true)) {
            $errors[] = 'فرمت فایل باید JPEG، PNG، WebP یا AVIF باشد.';
        }

        $maxMb = (int) config('virtual-tour.max_scene_image_size_mb', 50);
        if ($file->getSize() > $this->maxSizeBytes()) {
            $errors[] = "حداکثر حجم فایل {$maxMb} مگابایت است.";
        }

        $info = @getimagesize($file->getRealPath());
        if ($info) {
            $maxDim = $this->imageVariants->maxDimension();
            if ($info[0] > $maxDim || $info[1] > $maxDim) {
                $errors[] = "حداکثر اندازه تصویر {$maxDim} پیکسل است.";
            }
        }

        return ['valid' => empty($errors), 'errors' => $errors];
    }

    public function uploadSceneImage(
        User $user,
        int $tourId,
        UploadedFile $file,
        ?string $name = null,
        ?int $sceneId = null,
    ) {
        $validation = $this->validate($file);
        if (! $validation['valid']) {
            throw new \InvalidArgumentException(implode(' ', $validation['errors']));
        }

        $tour = $this->tourManager->findForOffice($user, $tourId);
        if ($tour->tour_type !== 'smart_walk') {
            throw new \InvalidArgumentException('این تور از نوع Smart Walk نیست.');
        }

        if ($sceneId) {
            return $this->sceneManager->updateFlatImage($user, $tourId, $sceneId, [
                'name' => $name,
            ], $file);
        }

        $sceneName = Str::limit(
            $name ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) ?: 'صحنه جدید',
            200
        );

        return $this->sceneManager->createFlatImage($user, $tourId, [
            'name' => $sceneName,
            'status' => 'draft',
        ], $file);
    }
}
