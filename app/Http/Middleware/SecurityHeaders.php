<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Defence in depth at the transport and browser layer. The CSP is what
 * stops an injected string from becoming executing script even if an
 * escaping mistake slips through somewhere in the views.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->add([
            'X-Content-Type-Options'    => 'nosniff',
            'X-Frame-Options'           => 'DENY',
            'Referrer-Policy'           => 'same-origin',
            'Permissions-Policy'        => 'geolocation=(), microphone=(), camera=()',
            'Content-Security-Policy'   => implode('; ', [
                "default-src 'self'",
                "script-src 'self'",
                "style-src 'self' https://fonts.googleapis.com",
                "font-src 'self' https://fonts.gstatic.com",
                "img-src 'self' data:",
                "form-action 'self'",
                "frame-ancestors 'none'",
                "base-uri 'self'",
            ]),
        ]);

        if (app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
