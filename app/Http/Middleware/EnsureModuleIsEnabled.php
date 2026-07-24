<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Modules\ModuleManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleIsEnabled
{
    public function __construct(private readonly ModuleManager $moduleManager) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $this->moduleManager->ensureEnabled($module);

        return $next($request);
    }
}
