<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiKeyMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $provided = $request->header('X-API-KEY');
        $expected = config('app.client_api_key'); // leemos del .env vía config/app.php

        if (!$expected || $provided !== $expected) {
            return response()->json(['ok' => false, 'error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
