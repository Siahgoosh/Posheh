<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ImpersonationSession;
use App\Models\User;
use App\Services\Admin\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminImpersonationController extends Controller
{
    public function __construct(private readonly AuditLogService $audit) {}

    public function start(Request $request, int $userId): JsonResponse
    {
        $target = User::with('office')->findOrFail($userId);

        if ($target->isPlatformStaff()) {
            return response()->json(['message' => 'ورود به حساب مدیران پلتفرم مجاز نیست.'], 422);
        }

        $session = ImpersonationSession::create([
            'admin_id' => $request->user()->id,
            'target_user_id' => $target->id,
            'office_id' => $target->office_id,
            'started_at' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        $token = $target->createToken('impersonation-'.$session->id)->plainTextToken;

        $this->audit->log(
            'impersonation.start',
            User::class,
            $target->id,
            "ورود به پنل {$target->name}",
            null,
            ['session_id' => $session->id, 'admin_id' => $request->user()->id],
        );

        return response()->json([
            'token' => $token,
            'user' => $target,
            'impersonation' => [
                'session_id' => $session->id,
                'admin_name' => $request->user()->name,
                'banner' => 'شما در حال مشاهده پنل به‌عنوان مدیر سیستم هستید.',
            ],
        ]);
    }

    public function end(Request $request): JsonResponse
    {
        $request->validate(['session_id' => ['required', 'integer']]);
        $session = ImpersonationSession::where('id', $request->integer('session_id'))
            ->where('admin_id', $request->user()->id)
            ->whereNull('ended_at')
            ->firstOrFail();

        $session->update(['ended_at' => now()]);
        $request->user()->currentAccessToken()?->delete();

        $this->audit->log('impersonation.end', User::class, $session->target_user_id, 'پایان impersonation');

        return response()->json(['message' => 'ورود جایگزین پایان یافت.']);
    }
}
