<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTermsAccepted
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->hasAcceptedCurrentTerms()) {
            return response()->json(['message' => 'Setujui ketentuan penggunaan terlebih dahulu.'], 403);
        }

        return $next($request);
    }
}
