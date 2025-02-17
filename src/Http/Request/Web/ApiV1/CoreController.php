<?php

declare(strict_types = 1);

namespace Weiran\System\Http\Request\Web\ApiV1;

use OpenApi\Attributes as OA;
use Weiran\Framework\Auth\ThrottlesLogins;
use Weiran\Framework\Classes\Resp;


/**
 * 系统信息控制
 */
class CoreController extends JwtApiController
{
    use ThrottlesLogins;

    #[OA\Post(
        path: '/api/web/v1/system/core/translate',
        summary: '多语言包',
        tags: ['Weiran'],
        responses: [
            new OA\Response(
                response: 200,
                description: '翻译信息',
                content: new OA\JsonContent(ref: '#/components/schemas/ResponseBaseBody')
            )
        ]
    )]
    public function translate()
    {
        return Resp::success('翻译信息', [
            'json'         => true,
            'translations' => app('translator')->fetch('zh'),
        ]);
    }


    #[OA\Post(
        path: '/api/web/v1/system/core/info',
        summary: '系统信息',
        tags: ['Weiran'],
        responses: [
            new OA\Response(
                response: 200,
                description: '系统信息',
                content: new OA\JsonContent(ref: '#/components/schemas/ResponseBaseBody')
            )
        ]
    )]
    public function info()
    {
        $hook   = sys_hook('weiran.system.api_info');
        $system = array_merge([], $hook);
        return Resp::success('获取系统配置信息', $system);
    }
}