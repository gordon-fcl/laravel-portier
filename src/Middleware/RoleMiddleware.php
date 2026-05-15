<?php

namespace Portier\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        $user = $request->user();

        if (! $user || ! method_exists($user, 'hasRole')) {
            throw new HttpException(403, 'Unauthorised.');
        }

        if (str_contains($roles, '&')) {
            $roleList = explode('&', $roles);
            foreach ($roleList as $role) {
                if (! $user->hasRole(trim($role))) {
                    throw new HttpException(403, 'Unauthorised.');
                }
            }
        } else {
            $roleList = explode('|', $roles);
            $hasAny = false;
            foreach ($roleList as $role) {
                if ($user->hasRole(trim($role))) {
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
