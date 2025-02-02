<?php

declare(strict_types = 1);

namespace Weiran\System\Http\Validation;

use Weiran\Framework\Application\Request;
use Weiran\Framework\Validation\Rule;
use Weiran\System\Models\PamAccount;

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
