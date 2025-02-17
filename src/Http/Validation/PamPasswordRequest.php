<?php

declare(strict_types = 1);

namespace Weiran\System\Http\Validation;

use OpenApi\Attributes as OA;
use Weiran\Framework\Application\Request;
use Weiran\Framework\Validation\Rule;

#[OA\Schema(
    schema: 'SystemPamPasswordRequest',
    required: ['password'],
    properties: [
        new OA\Property(
            property: 'verify_code',
            description: '方式1: 通过验证码获取到-> 验证串',
            type: 'string',
        ),
        new OA\Property(
            property: 'passport',
            description: '方式2: 手机号 + 验证码直接验证并修改',
            type: 'string',
        ),
        new OA\Property(
            property: 'captcha',
            description: '验证码',
            type: 'string',
        ),
        new OA\Property(
            property: 'password',
            description: '密码',
            type: 'string',
        ),
    ],
)]
class PamPasswordRequest extends Request
{

    public function attributes(): array
    {
        return [
            'password' => '密码',
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'password' => [
                Rule::required(),
                Rule::string(),
                Rule::simplePwd(),
                Rule::between(6, 20),
            ],
        ];
    }
}
