<?php

declare(strict_types = 1);

namespace Weiran\System\Http;

use Illuminate\Contracts\Http\Kernel as KernelContract;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Weiran\System\Http\Middlewares\CrossRequest;

class MiddlewareServiceProvider extends ServiceProvider
{
    public function boot(Router $router)
    {
        /* Single
         * ---------------------------------------- */
        $router->aliasMiddleware('sys-auth', Middlewares\Authenticate::class);
        $router->aliasMiddleware('sys-ban', Middlewares\Ban::class);
        $router->aliasMiddleware('sys-sso', Middlewares\Sso::class);
        $router->aliasMiddleware('sys-jwt', Middlewares\JwtAuthenticate::class);
        $router->aliasMiddleware('sys-auth_session', Middlewares\AuthenticateSession::class);
        $router->aliasMiddleware('sys-site_open', Middlewares\SiteOpen::class);
        $router->aliasMiddleware('sys-app_sign', Middlewares\AppSign::class);
        $router->aliasMiddleware('sys-html_purifier', Middlewares\HtmlPurifier::class);

        /*
        |--------------------------------------------------------------------------
        | Web Middleware
        |--------------------------------------------------------------------------
        |
        */
        /* 基于系统开关的权限验证
         * ---------------------------------------- */
        $router->middlewareGroup('web-base', [
            'web',
            'sys-site_open',
        ]);

        /* 独立的 web-auth 验证
         * ---------------------------------------- */
        $router->middlewareGroup('web-auth', [
            'web-base',
            'sys-auth:web',
            'sys-auth_session',
        ]);

        /* Web + Auth 进行验证
         * ---------------------------------------- */
        $router->middlewareGroup('web-with-auth', [
            'sys-auth:web',
            'sys-auth_session',
        ]);


        /*
        |--------------------------------------------------------------------------
        | Api Middleware
        |--------------------------------------------------------------------------
        */

        $router->middlewareGroup('api-sign', [
            'sys-ban',          // 系统禁用
            'sys-app_sign',     // 签名
            'sys-site_open',    // 站点开启
        ]);

        $router->middlewareGroup('api-sso', [
            'sys-app_sign',     // 签名
            'sys-site_open',    // 网站开启
            'sys-ban:user',     // 禁用
            'sys-sso',          // 单点登录
            'sys-auth:jwt_web', // 用户登录
        ]);


        // cors for api
        /** @var \Illuminate\Foundation\Http\Kernel $kernelContract */
        $kernelContract = $this->app->make(KernelContract::class);
        $kernelContract->prependMiddleware(CrossRequest::class);
    }
}