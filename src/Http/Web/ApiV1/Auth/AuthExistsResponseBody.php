<?php

declare(strict_types = 1);

namespace Weiran\System\Http\Web\ApiV1\Auth;

use OpenApi\Attributes as OA;
use Weiran\System\Http\OpenApi\BaseResponseBody;

#[OA\Schema(description: '通行证存在性检查')]
class AuthExistsResponseBody extends BaseResponseBody
{
    #[OA\Property(
        description: '检查结果',
        properties: [
            new OA\Property(property: 'is_exist', description: '是否存在', type: 'string', enum: ['Y', 'N']),
        ],
        type: 'object'
    )]
    public object $data;
}
