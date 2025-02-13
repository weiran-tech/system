<?php

declare(strict_types = 1);

namespace Weiran\System\Http\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Weiran\Framework\Classes\Resp;
use Weiran\Framework\Helper\EnvHelper;
use Weiran\Framework\Http\Middlewares\EnableCrossRequest;

/**
 * 添加跨域登录的限制
 */
class CrossRequest extends EnableCrossRequest
{

    /**
     * Middleware handler.
     * @param Request $request request
     * @param Closure $next    next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $origin = config('weiran.system.cross_origin');
        if (!$origin) {
            $origin = '*';
        }
        if (is_array($origin)) {
            $schema    = EnvHelper::scheme();
            $domain    = EnvHelper::domain();
            $reqDomain = "{$schema}{$domain}";
            if (in_array($reqDomain, $origin, true)) {
                $origin = $reqDomain;
            }
            else {
                return Resp::error('跨域访问, 访问受限');
            }
        }
        $header = config('weiran.system.cross_headers');
        if (is_array($header)) {
            $header = implode(',', $header);
        }
        $headers = collect([
            'Access-Control-Allow-Origin'      => $origin,
            'Access-Control-Allow-Headers'     => 'Origin,Content-Type,Cookie,Accept,Authorization,X-Requested-With' . ($header ? ',' . $header : ''),
            'Access-Control-Allow-Methods'     => 'DELETE,GET,POST,PATCH,PUT,OPTIONS',
            'Access-Control-Allow-Credentials' => 'true',
        ]);

        return $this->respWithHeaders($headers, $request, $next);
    }
}