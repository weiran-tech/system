<?php

declare(strict_types = 1);

namespace Weiran\System\Http\Middlewares;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Factory;
use Illuminate\Session\Middleware\AuthenticateSession as BaseAuthenticateSession;
use Tymon\JWTAuth\JWTGuard;

/**
 * User Session Auth Validation
 * If User Password Changed, Other User In Session are logout
 * 注意项目 :
 * 1. 用户登录成功之后需要触发重新计算 password_hash, 如果不清空则二次登录的时候会自动再次退出一次
 * 2. 此中间件需要放置在 auth:xx 之后
 */
class AuthenticateSession extends BaseAuthenticateSession
{

    /**
     * @var Factory|SessionGuard
     */
    protected $auth;

    /**
     * @inheritDoc
     * @throws AuthenticationException
     */
    public function handle($request, Closure $next)
    {
        /* Jwt 不进行 Session 权限校验
         * ---------------------------------------- */
        if ($this->auth->guard() instanceof JWTGuard) {
            return $next($request);
        }

        if (!$request->user() || !$request->session()) {
            return $next($request);
        }

        if ($this->auth->viaRemember()) {
            // 这里清理用户/如果登录不一致[不同的guard]
            $passwordHash = explode('|', $request->cookies->get($this->auth->getRecallerName()))[2];
            if ($passwordHash != $request->user()->getAuthPassword()) {
                $this->logout($request);
            }
        }
        $loginSessionKey = $this->auth->guard()->getName();
        $hashKey         = self::hashKey($loginSessionKey);

        // 重新存储 hash
        if (!$request->session()->has($hashKey)) {
            $this->storePasswordHashInSession($request);
        }

        // 检测用户密码是否发生过改动
        if ($request->session()->get($hashKey) !== $request->user()->getAuthPassword()) {
            $this->logout($request);
        }

        // 登录完成后再进行存储
        return tap($next($request), function () use ($request) {
            $this->storePasswordHashInSession($request);
        });
    }

    /**
     * Password hash key
     * @param string $login_key login key
     * @return string
     */
    public static function hashKey(string $login_key): string
    {
        $guard = self::guardName($login_key);
        return 'password_hash' . ($guard ? '_' . $guard : '');
    }

    /**
     * Guard 名称
     * @param string $guard GuardName
     * @return string
     */
    public static function hashGuard(string $guard): string
    {
        return "password_hash_{$guard}";
    }

    /**
     * @inheritDoc
     */
    protected function storePasswordHashInSession($request)
    {
        if (!$request->user()) {
            return;
        }

        $loginSessionKey = $this->auth->guard()->getName();

        $request->session()->put([
            self::hashKey($loginSessionKey) => $request->user()->getAuthPassword(),
        ]);
    }

    /**
     * @inheritDoc
     */
    protected function logout($request)
    {
        $this->auth->logoutCurrentDevice();

        $request->session()->flush();

        $loginSessionKey = $this->auth->guard()->getName();
        $guards          = [self::guardName($loginSessionKey)];
        throw new AuthenticationException('无权访问', $guards, Authenticate::detectLocation($guards));
    }

    private static function guardName(string $login_key): string
    {
        $guard = '';
        if (preg_match('/login_(?<guard>.*?)_/', $login_key, $match)) {
            $guard = $match['guard'];
        }
        return $guard;
    }
}