<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menambahkan header keamanan dasar pada setiap respons.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! headers_sent()) {
            header_remove('X-Powered-By');
        }

        $response->headers->remove('X-Powered-By');

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set(
            'Permissions-Policy',
            'camera=(self), microphone=(), geolocation=(), payment=()'
        );
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin-allow-popups');

        // Konten hanya boleh dimuat dari aplikasi sendiri, kecuali library/asset
        // yang memang dipakai (Chart.js, scanner QR, font, dan generator QR).
        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; "
            . "base-uri 'self'; "
            . "object-src 'none'; "
            . "frame-ancestors 'self'; "
            . "form-action 'self'; "
            . "script-src 'self' 'unsafe-inline' blob: https://cdn.jsdelivr.net https://unpkg.com; "
            . "style-src 'self' 'unsafe-inline' https://fonts.bunny.net; "
            . "font-src 'self' data: https://fonts.bunny.net; "
            . "img-src 'self' data: blob: https://api.qrserver.com; "
            . "media-src 'self' blob:; "
            . "worker-src 'self' blob:; "
            . "connect-src 'self';"
        );

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
