<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppGuideController extends Controller
{
    public function complete(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->markAppGuideCompleted();

        return response()->json([
            'completed' => true,
            'app_guide_completed_at' => $user->fresh()?->app_guide_completed_at?->toIso8601String(),
        ]);
    }

    public function restart(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->resetAppGuide();

        return response()->json([
            'completed' => false,
            'app_guide_completed_at' => null,
        ]);
    }
}
