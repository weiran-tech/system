<?php

declare(strict_types = 1);

namespace Weiran\System\Http\Middlewares;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate as IlluminateAuthenticate;
use Weiran\Framework\Classes\Resp;
use Weiran\System\Models\PamAccount;
use Weiran\System\Models\SysConfig;

/**
 * Class Authenticate.
 */
class Authenticate extends IlluminateAuthenticate
{

    private bool $isJwt = false;

    /**
     * 检测跳转地址
     * @param $guards
     * @return string
     */
    public static function detectLocation($guards): string
    {
        $location = '';
        if (in_array(PamAccount::GUARD_BACKEND, $guards, true) && $backendLogin = config('weiran.framework.prefix', 'mgr-page') . '/login') {
            $location = $backendLogin;
        }
        if (in_array(PamAccount::GUARD_WEB, $guards, true) && $userLogin = config('weiran.system.user_location')) {
            $location = $userLogin;
        }
        return $location;
    }

    /**
     * @inheritDoc
     */
    public function handle($request, Closure $next, ...$guards)
    {
        try {
            $this->authenticate($request, $guards);
        } catch (AuthenticationException $e) {
            if ($this->isJwt || $request->expectsJson()) {
                return response()->json([
                    'status'  => 401,
                    'message' => $e->getMessage(),
                ], 401, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            if ($location = self::detectLocation($guards)) {
                return Resp::error('无权限访问', [
                    '_location' => $location,
                    '_time'     => false,
                ]);
            }

            return response('Unauthorized.', 401);
        }
        return $next($request);
    }

    /**
     * @inheritDoc
     */
    protected function authenticate($request, array $guards)
    {
        if (empty($guards)) {
            return app('auth')->authenticate();
        }
        $extendGuards = [
            'backend' => 'jwt_backend',
            'web'     => 'jwt_web',
        ];
        if (($type = x_header('type')) && isset($extendGuards[$type])) {
            $guards = array_merge($extendGuards, [$extendGuards[$type]]);
        }
        foreach ($guards as $guard) {
            if (app('auth')->guard($guard)->check()) {
                /** @var PamAccount $user */
                $user = app('auth')->guard($guard)->user();
                if ($user->is_enable === SysConfig::NO) {
                    $reason      = '用户被禁用' . ($user->disable_reason ? ', 原因: ' . $user->disable_reason : '') . ', 解禁时间 : ' . $user->disable_end_at;
                    $this->isJwt = (bool) jwt_token();
                    throw new AuthenticationException($reason, $guards);
                }
                app('auth')->shouldUse($guard);
                return true;
            }
        }
        throw new AuthenticationException('Unauthenticated.', $guards);
    }
}