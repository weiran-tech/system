<?php

declare(strict_types = 1);

namespace Weiran\System\Http\Web\ApiV1\Core;

use OpenApi\Attributes as OA;
use Weiran\System\Http\OpenApi\BaseResponseBody;

#[OA\Schema(description: '系统信息')]
class CoreInfoResponseBody extends BaseResponseBody
{
    #[OA\Property(
        description: '系统配置与 Hook 聚合信息',
        type: 'object',
        additionalProperties: true
    )]
    public object $data;
}
