<?php

declare(strict_types = 1);

namespace Weiran\System\Http\Validation;

use OpenApi\Attributes as OA;
use Weiran\Framework\Application\Request;
use Weiran\Framework\Validation\Rule;
use Weiran\System\Models\PamAccount;

#[OA\Schema(
    required: ['passport'],
    properties: [
        new OA\Property(
            property: 'passport',
            description: '通行证',
            type: 'string',
        ),
        new OA\Property(
            property: 'password',
            description: '密码',
        ),
        new OA\Property(
            property: 'captcha',
            description: '验证码',
        ),
        new OA\Property(
            property: 'device_id',
            description: '设备ID',
            type: 'string',
        ),
        new OA\Property(
            property: 'device_type',
            description: '设备类型',
            type: 'string',
        ),
        new OA\Property(
            property: 'guard',
            description: '登录类型[web|用户(默认);backend|后台;]',
            type: 'string',
            enum: [PamAccount::GUARD_WEB, PamAccount::GUARD_BACKEND],
        ),
    ]
)]
class PamLoginRequest extends Request
{

    protected bool $isValidate = false;

    public function attributes(): array
    {
        return [
            'passport' => '通行证',
            'captcha'  => '验证码',
            'password' => '密码',
            'os'       => '软件平台',
        ];
    }

    public function scenes(): array
    {
        return [
            'passport' => [
                'passport', 'os',
            ],
            'captcha'  => [
                'passport', 'captcha', 'os',
            ],
            'password' => [
                'passport', 'password', 'os',
            ],
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
            'passport' => [
                Rule::required(),
            ],
            'captcha'  => [
                Rule::required(),
                Rule::numeric(),
            ],
            'password' => [
                Rule::required(),
                Rule::string(),
                Rule::simplePwd(),
                Rule::between(6, 20),
            ],
            'os'       => [
                Rule::required(),
                Rule::in(PamAccount::kvPlatform()),
            ],
        ];
    }
}
