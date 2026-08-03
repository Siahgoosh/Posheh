<?php

namespace App\Modules\VirtualTour\Application\Services;

use App\Models\User;
use App\Models\VirtualTourMedia;
use App\Modules\VirtualTour\Application\Contracts\PanoramaStorageInterface;
use Illuminate\Http\UploadedFile;

class PanoramaUploader
{
  private const MAX_SIZE_BYTES = 100 * 1024 * 1024; // 100MB

  private const ALLOWED_MIMES = [
    'image/jpeg',
    'image/jpg',
    'image/png',
    'image/webp',
  ];

  public function __construct(
    private readonly TourManager $tourManager,
    private readonly SceneManager $sceneManager,
    private readonly PanoramaStorageInterface $storage,
  ) {}

  /**
   * @return array{valid: bool, errors: string[]}
   */
  public function validate(UploadedFile $file): array
  {
    $errors = [];

    if (! in_array($file->getMimeType(), self::ALLOWED_MIMES, true)) {
      $errors[] = 'فرمت فایل باید JPEG، PNG یا WebP باشد.';
    }

    if ($file->getSize() > self::MAX_SIZE_BYTES) {
      $errors[] = 'حداکثر حجم فایل ۱۰۰ مگابایت است.';
    }

    $info = @getimagesize($file->getRealPath());
    if ($info) {
      $ratio = $info[0] / max(1, $info[1]);
      if ($ratio < 1.8 || $ratio > 2.2) {
        $errors[] = 'تصویر باید equirectangular با نسبت ۲:۱ باشد.';
      }
    }

    return ['valid' => empty($errors), 'errors' => $errors];
  }

  public function uploadScenePanorama(
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

    if ($sceneId) {
      return $this->sceneManager->update($user, $tourId, $sceneId, [
        'name' => $name,
      ], $file);
    }

    $sceneName = $name ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

    return $this->sceneManager->create($user, $tourId, [
      'name' => $sceneName,
      'status' => 'draft',
    ], $file);
  }

  public function uploadMedia(User $user, int $tourId, UploadedFile $file, string $type, ?string $title = null): VirtualTourMedia
  {
    $tour = $this->tourManager->findForOffice($user, $tourId);
    $path = $file->store("virtual-tours/{$tour->id}/media", 'public');

    return $tour->media()->create([
      'type' => $type,
      'path' => $path,
      'title' => $title ?? $file->getClientOriginalName(),
      'sort_order' => $tour->media()->count(),
    ]);
  }
}
