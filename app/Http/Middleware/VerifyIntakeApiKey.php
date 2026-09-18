<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyIntakeApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('crm.intake_api_key');
        $provided = (string) ($request->header('X-Api-Key') ?: $request->bearerToken());

        if ($expected === '' || ! hash_equals($expected, $provided)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid or missing API key',
            ], 401);
        }

        return $next($request);
    }
}
