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

        if (($tour->visibility ?? 'public') === 'private') {
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

        if ($request->boolean('embed')) {
            $this->assertEmbedAllowed($tour, $request);
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

    public function assertEmbedAllowed(VirtualTour $tour, Request $request): void
    {
        $settings = $tour->settings ?? [];

        if (($settings['embed_enabled'] ?? true) === false) {
            throw new AccessDeniedHttpException('Embed برای این تور غیرفعال است.');
        }

        $allowed = $settings['embed_allowed_domains'] ?? [];
        if (! is_array($allowed) || empty($allowed)) {
            return;
        }

        $source = $request->header('Referer') ?? $request->header('Origin');
        if (! $source) {
            throw new AccessDeniedHttpException('Embed فقط از دامنه‌های مجاز قابل نمایش است.');
        }

        $host = parse_url($source, PHP_URL_HOST);
        if (! $host) {
            throw new AccessDeniedHttpException('دامنه embed معتبر نیست.');
        }

        $host = strtolower($host);
        foreach ($allowed as $domain) {
            $domain = strtolower(trim((string) $domain));
            if ($domain === '' || $domain === '*') {
                return;
            }
            if ($host === $domain || str_ends_with($host, '.'.$domain)) {
                return;
            }
        }

        throw new AccessDeniedHttpException('دامنه embed مجاز نیست.');
    }

    private function loadTourRelations(VirtualTour $tour): VirtualTour
    {
        return $tour->load([
            'scenes' => fn ($q) => $q
                ->where('is_visible', true)
                ->where(function ($q) {
                    $q->where('status', 'published')
                        ->orWhereNull('status');
                })
                ->orderBy('sort_order'),
            'scenes.hotspots.targetScene',
            'media',
            'property',
            'office:id,name,phone,logo_path',
        ]);
    }
}
