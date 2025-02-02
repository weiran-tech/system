<?php

declare(strict_types = 1);

namespace Weiran\System\Http\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Weiran\Framework\Classes\Resp;
use Weiran\Framework\Classes\Traits\AppTrait;
use Weiran\System\Classes\Contracts\ApiSignContract;

/**
 * 是否开启App 接口加密
 */
class AppSign
{
    use AppTrait;

    /**
     * Handle an incoming request.
     * @param Request $request 请求
     * @param Closure $next    后续处理
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $Sign = app(ApiSignContract::class);

        if (!$Sign->check($request)) {
            $error = $Sign->getError();
            return Resp::web($error->getCode(), $error->getMessage());
        }
        return $next($request);
    }
}