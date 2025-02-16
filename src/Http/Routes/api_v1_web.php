<?php

declare(strict_types = 1);

/*
|--------------------------------------------------------------------------
| 系统路由
|--------------------------------------------------------------------------
|
*/

/* 核心信息无需禁用, 仅需要加密鉴权即可
 * ---------------------------------------- */
Route::group([
    'middleware' => ['sys-app_sign'],
    'namespace'  => 'Poppy\System\Http\Request\Web\ApiV1',
], function (Illuminate\Routing\Router $route) {
    $route->post('v1/core/info', 'CoreController@info');
    $route->post('v1/core/translate', 'CoreController@translate');
});

/* 可以对用户设备进行封禁
 * ---------------------------------------- */
Route::group([
    'middleware' => ['api-sign'],
    'namespace'  => 'Poppy\System\Http\Request\Web\ApiV1',
], function (Illuminate\Routing\Router $route) {
    $route->post('v1/auth/login', 'AuthController@login')
        ->name('weiran-system:pam.auth.login');
    $route->post('v1/auth/exists', 'AuthController@exists');

    // captcha
    $route->post('v1/captcha/verify_code', 'CaptchaController@verifyCode');
    $route->post('v1/captcha/send', 'CaptchaController@send');

    // auth
    $route->post('v1/auth/reset_password', 'AuthController@resetPassword');
    $route->post('v1/auth/bind_mobile', 'AuthController@bindMobile');
});

// Jwt 合法性验证
Route::group([
    'middleware' => ['sys-jwt'],
    'namespace'  => 'Poppy\System\Http\Request\Web\ApiV1',
], function (Illuminate\Routing\Router $route) {
    $route->post('v1/upload/image', 'UploadController@image')
        ->name('weiran-system:api_v1.upload.image');
    $route->post('v1/upload/file', 'UploadController@file')
        ->name('weiran-system:api_v1.upload.file');
});

// 单点登录
Route::group([
    'middleware' => ['api-sso'],
    'namespace'  => 'Poppy\System\Http\Request\Web\ApiV1',
], function (Illuminate\Routing\Router $route) {
    $route->post('v1/auth/access', 'AuthController@access')
        ->name('weiran-system:pam.auth.access');
    $route->post('v1/auth/renew', 'AuthController@renew')
        ->name('weiran-system:pam.auth.renew');
    $route->post('v1/auth/logout', 'AuthController@logout')
        ->name('weiran-system:pam.auth.logout');
});
