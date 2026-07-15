<?php

namespace App\Services\Office;

use App\Models\Office;
use App\Models\OfficeSitePost;
use App\Models\OfficeVisitRequest;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OfficeSiteService
{
    public function requestWebsite(User $user, array $data): Office
    {
        $office = $user->office;
        abort_unless($user->canManageOffice(), 403);

        if (! in_array($office->panel_type, ['premium', 'office'], true)) {
            throw ValidationException::withMessages([
                'subdomain' => ['ساخت وبسایت اختصاصی در پلن حرفه‌ای فعال است.'],
            ]);
        }

        $subdomain = $this->normalizeSubdomain($data['subdomain'] ?? $office->slug);

        if (Office::where('subdomain', $subdomain)->where('id', '!=', $office->id)->exists()) {
            throw ValidationException::withMessages([
                'subdomain' => ['این زیردامنه قبلاً ثبت شده است.'],
            ]);
        }

        $office->update([
            'subdomain' => $subdomain,
            'website_description' => $data['description'] ?? $office->description,
            'website_status' => 'pending',
        ]);

        return $office->fresh();
    }

    public function publishPost(User $user, array $data): OfficeSitePost
    {
        $office = $user->office;
        abort_unless($user->canManageOffice(), 403);
        abort_unless(in_array($office->website_status, ['approved', 'published'], true), 403, 'وبسایت هنوز تأیید نشده است.');

        $slug = Str::slug($data['title'] ?? 'post').'-'.Str::random(4);

        return OfficeSitePost::create([
            'office_id' => $office->id,
            'property_id' => $data['property_id'] ?? null,
            'created_by' => $user->id,
            'title' => $data['title'],
            'slug' => $slug,
            'excerpt' => $data['excerpt'] ?? null,
            'body' => $data['body'] ?? null,
            'is_published' => $data['is_published'] ?? true,
        ]);
    }

    public function publicSite(string $subdomain): array
    {
        $office = Office::with(['plan'])
            ->where('subdomain', $subdomain)
            ->where('website_status', 'published')
            ->where('plan_active', true)
            ->where('is_active', true)
            ->firstOrFail();

        $properties = Property::where('office_id', $office->id)
            ->latest()
            ->limit(24)
            ->get(['id', 'code', 'type', 'property_category', 'price', 'deposit', 'rent', 'area', 'city', 'district', 'neighborhood', 'description']);

        $posts = OfficeSitePost::where('office_id', $office->id)
            ->where('is_published', true)
            ->latest()
            ->limit(12)
            ->get();

        return [
            'office' => [
                'name' => $office->name,
                'subdomain' => $office->subdomain,
                'city' => $office->city,
                'address' => $office->address,
                'phone' => $office->phone,
                'description' => $office->website_description ?? $office->description,
                'is_verified' => $office->is_verified,
                'logo_url' => $office->logo_path ? url('storage/'.$office->logo_path) : null,
                'url' => 'https://'.$office->subdomain.'.posheapp.ir',
            ],
            'properties' => $properties,
            'posts' => $posts,
        ];
    }

    public function submitVisitRequest(string $subdomain, array $data): OfficeVisitRequest
    {
        $office = Office::where('subdomain', $subdomain)
            ->where('website_status', 'published')
            ->firstOrFail();

        return OfficeVisitRequest::create([
            'office_id' => $office->id,
            'property_id' => $data['property_id'] ?? null,
            'name' => $data['name'],
            'mobile' => $data['mobile'],
            'message' => $data['message'] ?? null,
            'status' => 'new',
        ]);
    }

    public function adminApproveWebsite(Office $office, string $action): Office
    {
        $status = match ($action) {
            'approve' => 'approved',
            'publish' => 'published',
            'reject' => 'rejected',
            'unpublish' => 'approved',
            default => throw ValidationException::withMessages(['action' => ['عملیات نامعتبر']]),
        };

        $updates = ['website_status' => $status];
        if ($status === 'published') {
            $updates['website_published_at'] = now();
            $updates['show_on_website'] = true;
        }

        $office->update($updates);

        return $office->fresh();
    }

    public function adminTogglePlan(Office $office, bool $active): Office
    {
        $office->update(['plan_active' => $active]);

        if (! $active) {
            $office->update(['is_active' => false]);
        } else {
            $office->update(['is_active' => true]);
        }

        return $office->fresh();
    }

    private function normalizeSubdomain(string $value): string
    {
        $value = Str::slug($value);
        $value = preg_replace('/[^a-z0-9-]/', '', $value) ?? $value;

        return substr($value, 0, 63);
    }
}
