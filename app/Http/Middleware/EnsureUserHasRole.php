<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('login'));
        }

        $allowedRoles = array_intersect($roles, UserRole::values());

        if ($allowedRoles === [] || ! $user->hasAnyRole($allowedRoles)) {
            abort(Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
