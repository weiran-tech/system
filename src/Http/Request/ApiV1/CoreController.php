<?php

declare(strict_types = 1);

namespace Weiran\System\Http\Request\ApiV1;

use Weiran\Framework\Auth\ThrottlesLogins;
use Weiran\Framework\Classes\Resp;

/**
 * 系统信息控制
 */
class CoreController extends JwtApiController
{
    use ThrottlesLogins;

    /**
     * @api                   {post} /api_v1/system/core/translate [Sys]多语言包
     * @apiVersion            1.0.0
     * @apiName               SysCoreTranslate
     * @apiGroup              Poppy
     */
    public function translate()
    {
        return Resp::success('翻译信息', [
            'json'         => true,
            'translations' => app('translator')->fetch('zh'),
        ]);
    }


    /**
     * @api                   {post} /api_v1/system/core/info [Sys]系统信息
     * @apiVersion            1.0.0
     * @apiName               SysCoreInfo
     * @apiGroup              Poppy
     */
    public function info()
    {
        $hook   = sys_hook('poppy.system.api_info');
        $system = array_merge([], $hook);
        return Resp::success('获取系统配置信息', $system);
    }
}