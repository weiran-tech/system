<?php

declare(strict_types = 1);

namespace Weiran\System\Http\Web\ApiV1;

use OpenApi\Attributes as OA;
use Weiran\Framework\Auth\ThrottlesLogins;
use Weiran\Framework\Classes\Resp;
use Weiran\System\Http\OpenApi\BaseResponseBody;

/**
 * 系统信息控制
 */
class CoreController extends JwtApiController
{
    use ThrottlesLogins;

    #[OA\Post(
        path: '/api/web/system/v1/core/translate',
        summary: '多语言包',
        tags: ['System'],
        responses: [
            new OA\Response(
                response: 200,
                description: '翻译信息',
                content: new OA\JsonContent(ref: BaseResponseBody::class)
            ),
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
        path: '/api/web/system/v1/core/info',
        summary: '系统信息',
        tags: ['System'],
        responses: [
            new OA\Response(
                response: 200,
                description: '系统信息',
                content: new OA\JsonContent(ref: BaseResponseBody::class)
            ),
        ]
    )]
    public function info()
    {
        $hook   = sys_hook('weiran.system.api_info');
        $system = array_merge([], $hook);

        return Resp::success('获取系统配置信息', $system);
    }
}
