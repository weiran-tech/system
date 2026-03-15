<?php

declare(strict_types = 1);

namespace Weiran\System\Http\Web\ApiV1\Captcha;

use OpenApi\Attributes as OA;
use Weiran\System\Http\OpenApi\BaseResponseBody;

#[OA\Schema(description: '验证码验证成功')]
class CaptchaVerifyCodeResponseBody extends BaseResponseBody
{
    #[OA\Property(
        description: '生成的一次性验证串',
        properties: [
            new OA\Property(property: 'verify_code', description: '一次性验证串', type: 'string'),
        ],
        type: 'object'
    )]
    public object $data;
}
