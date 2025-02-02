<?php
declare(strict_types = 1);

namespace Weiran\System\Http\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class RequestIdMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $requestId = Str::uuid()->toString();

        $request->requestId = $requestId;

        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }
}