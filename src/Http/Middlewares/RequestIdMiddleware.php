<?php

declare(strict_types = 1);

namespace Weiran\System\Http\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;

class RequestIdMiddleware
{
    public function __construct(
        protected LogManager $log
    ) {}

    public function handle(Request $request, Closure $next)
    {
        $requestId = Str::uuid()->toString();

        $request->attributes->set('requestId', $requestId);

        // 加入 request Id
        $this->log->withContext(['requestId' => $requestId]);

        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }
}
