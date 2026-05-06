<?php

namespace Portier\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, string $permissions): Response
    {
        $user = $request->user();

        if (! $user || ! method_exists($user, 'hasPermission')) {
            throw new HttpException(403, 'Unauthorised.');
        }

        if (str_contains($permissions, '&')) {
            $permList = explode('&', $permissions);
            foreach ($permList as $permission) {
                if (! $user->hasPermission(trim($permission))) {
                    throw new HttpException(403, 'Unauthorised.');
                }
            }
        } else {
            $permList = explode('|', $permissions);
            $hasAny = false;
            foreach ($permList as $permission) {
                if ($user->hasPermission(trim($permission))) {
                    $hasAny = true;
                    break;
                }
            }
            if (! $hasAny) {
                throw new HttpException(403, 'Unauthorised.');
            }
        }

        return $next($request);
    }
}
