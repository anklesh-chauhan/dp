<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->mustChangePassword()) {
            return $next($request);
        }

        if ($request->routeIs('filament.admin.auth.logout', 'filament.admin.auth.profile', 'filament.admin.auth.profile.*')) {
            return $next($request);
        }

        $profileUrl = Filament::getProfileUrl();

        if (! is_string($profileUrl) || $profileUrl === '') {
            return $next($request);
        }

        return redirect($profileUrl);
    }
}
