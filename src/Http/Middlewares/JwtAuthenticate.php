<?php

declare(strict_types = 1);

namespace Weiran\System\Http\Middlewares;

use Closure;
use Weiran\System\Models\PamAccount;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;

/**
 * Jwt 校验
 * - 用在 Auth 前验证Token 存在以及Token 是否有效(不进行数据库查询)
 * - 用在 Auth 之后, 校验用户密码变更匹配, 需要 Claims 返回 user.salt
 */
class JwtAuthenticate extends BaseMiddleware
{
    public function handle($request, Closure $next)
    {
        /** @var PamAccount $user */
        $user = $request->user();

        $token = $this->auth->setRequest($request)->getToken();

        if (!$token || !$payload = $this->auth->check(true)) {
            return response('Unauthorized Jwt.', 401);
        }
        if ($user) {
            $salt     = md5(sha1($user->password_key) . $user->password);
            $userSalt = data_get($payload, 'user.salt');
            if (!$userSalt) {
                return $next($request);
            }
            if ($salt !== $userSalt) {
                return response('Unauthorized Jwt.', 401);
            }
        }
        return $next($request);
    }
}