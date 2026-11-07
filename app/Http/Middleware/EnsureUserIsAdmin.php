<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Membatasi akses route admin hanya untuk pengguna dengan role admin.
 */
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(
            $request->user() && $request->user()->isAdmin(),
            403,
            'Halaman ini hanya dapat diakses admin.'
        );

        return $next($request);
    }
}
