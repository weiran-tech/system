<?php

declare(strict_types = 1);

namespace Weiran\System\Http\Middlewares;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Weiran\System\Classes\PySystemDef;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;

/**
 * 单点登录
 */
class Sso extends BaseMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $token = jwt_token();

        try {
            if (!$token || !$payload = $this->auth->setToken($token)->check(true)) {
                return response('Unauthorized Jwt.', 401);
            }
            // 这里会抛出异常, IDE 提示不正确
        } catch (Exception $e) {
            return response('Unauthorized Jwt. Sso check token invalid', 401);
        }

        // 是否开启单点登录
        if (!\Weiran\System\Action\Sso::isEnable()) {
            return $next($request);
        }

        // 组无限, 对于指定的组 KEY 进行不设限标识
        if ((new \Weiran\System\Action\Sso())->groupType(x_header('os')) === \Weiran\System\Action\Sso::GROUP_UNLIMITED) {
            return $next($request);
        }

        // sso check
        $md5Token = md5($token);
        $pamId    = data_get($payload, 'sub');

        $devices = sys_tag('weiran-system-persist')->hGet(PySystemDef::ckPersistSsoValid(), $pamId);
        if (!$devices) {
            return response('Unauthorized Jwt, No valid device.', 401);
        }
        if (array_key_exists($md5Token, $devices)) {
            return $next($request);
        }
        return response('Unauthorized Jwt, Token unValid.', 401);
    }
}