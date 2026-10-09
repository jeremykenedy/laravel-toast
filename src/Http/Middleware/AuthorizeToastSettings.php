<?php

declare(strict_types=1);

namespace Jeremykenedy\LaravelToast\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Jeremykenedy\LaravelToast\Support\ToastSettings;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeToastSettings
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(ToastSettings::canManage($request->user()), 403);

        return $next($request);
    }
}
