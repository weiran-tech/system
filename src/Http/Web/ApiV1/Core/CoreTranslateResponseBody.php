<?php

declare(strict_types = 1);

namespace Weiran\System\Http\Web\ApiV1\Core;

use OpenApi\Attributes as OA;
use Weiran\System\Http\OpenApi\BaseResponseBody;

#[OA\Schema(description: '多语言包')]
class CoreTranslateResponseBody extends BaseResponseBody
{
    #[OA\Property(
        description: '多语言包数据',
        properties: [
            new OA\Property(property: 'json', description: '是否 JSON 格式', type: 'boolean'),
            new OA\Property(
                property: 'translations',
                description: '翻译内容键值表',
                type: 'object',
                additionalProperties: true
            ),
        ],
        type: 'object'
    )]
    public object $data;
}
