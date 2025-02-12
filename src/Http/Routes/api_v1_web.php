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
    'namespace'  => 'Poppy\System\Http\Request\ApiV1',
], function (Illuminate\Routing\Router $route) {
    $route->post('core/info', 'CoreController@info');
    $route->post('core/translate', 'CoreController@translate');
});

/* 可以对用户设备进行封禁
 * ---------------------------------------- */
Route::group([
    'middleware' => ['api-sign'],
    'namespace'  => 'Poppy\System\Http\Request\ApiV1',
], function (Illuminate\Routing\Router $route) {
    $route->post('auth/login', 'AuthController@login')
        ->name('weiran-system:pam.auth.login');
    $route->post('auth/exists', 'AuthController@exists');

    // captcha
    $route->post('captcha/verify_code', 'CaptchaController@verifyCode');
    $route->post('captcha/send', 'CaptchaController@send');

    // auth
    $route->post('auth/reset_password', 'AuthController@resetPassword');
    $route->post('auth/bind_mobile', 'AuthController@bindMobile');
});

// Jwt 合法性验证
Route::group([
    'middleware' => ['sys-jwt'],
    'namespace'  => 'Poppy\System\Http\Request\ApiV1',
], function (Illuminate\Routing\Router $route) {
    $route->post('upload/image', 'UploadController@image')
        ->name('weiran-system:api_v1.upload.image');
    $route->post('upload/file', 'UploadController@file')
        ->name('weiran-system:api_v1.upload.file');
});

// 单点登录
Route::group([
    'middleware' => ['api-sso'],
    'namespace'  => 'Poppy\System\Http\Request\ApiV1',
], function (Illuminate\Routing\Router $route) {
    $route->post('auth/access', 'AuthController@access')
        ->name('weiran-system:pam.auth.access');
    $route->post('auth/renew', 'AuthController@renew')
        ->name('weiran-system:pam.auth.renew');
    $route->post('auth/logout', 'AuthController@logout')
        ->name('weiran-system:pam.auth.logout');
});
