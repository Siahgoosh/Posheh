<?php

namespace App\Modules\VirtualTour\Application\Services;

use App\Models\VirtualTour;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TourAccessService
{
    public function resolvePublicTour(string $slug, Request $request, bool $allowPasswordGate = true): VirtualTour
    {
        $tour = VirtualTour::where('slug', $slug)
            ->where('status', 'published')
            ->whereNull('archived_at')
            ->first();

        if (! $tour) {
            throw new NotFoundHttpException('تور مجازی یافت نشد.');
        }

        if ($tour->expires_at && $tour->expires_at->isPast()) {
            throw new GoneHttpException('این تور منقضی شده است.');
        }

        if ($tour->visibility === 'private') {
            $token = $request->query('token') ?? $request->header('X-Tour-Token');
            if (! $token || ! hash_equals($tour->share_token ?? '', $token)) {
                throw new AccessDeniedHttpException('دسترسی به این تور خصوصی مجاز نیست.');
            }
        }

        if ($tour->access_password && $allowPasswordGate) {
            $password = $request->input('password') ?? $request->header('X-Tour-Password');
            if (! $password || ! Hash::check($password, $tour->access_password)) {
                throw new AccessDeniedHttpException('رمز دسترسی الزامی است.');
            }
        }

        return $this->loadTourRelations($tour);
    }

    public function verifyPassword(VirtualTour $tour, string $password): bool
    {
        if (! $tour->access_password) {
            return true;
        }

        return Hash::check($password, $tour->access_password);
    }

    public function getAccessMeta(VirtualTour $tour): array
    {
        return [
            'visibility' => $tour->visibility ?? 'public',
            'requires_password' => (bool) $tour->access_password,
            'expires_at' => $tour->expires_at?->toIso8601String(),
            'is_expired' => $tour->expires_at?->isPast() ?? false,
        ];
    }

    public function findForAccessCheck(string $slug): VirtualTour
    {
        return VirtualTour::where('slug', $slug)
            ->where('status', 'published')
            ->whereNull('archived_at')
            ->firstOrFail();
    }

    private function loadTourRelations(VirtualTour $tour): VirtualTour
    {
        return $tour->load([
            'scenes' => fn ($q) => $q->where('is_visible', true)->where('status', 'published'),
            'scenes.hotspots.targetScene',
            'media',
            'property',
            'office:id,name,phone,logo_path',
        ]);
    }
}
