<?php

declare(strict_types = 1);

namespace Weiran\System\Http\Validation;

use OpenApi\Attributes as OA;
use Weiran\Framework\Application\Request;
use Weiran\Framework\Validation\Rule;

#[OA\Schema(
    schema: 'SystemPamBindMobileRequest',
    required: ['passport', 'captcha', 'verify_code'],
    properties: [
        new OA\Property(property: 'passport', description: '通行证', type: 'string'),
        new OA\Property(property: 'verify_code', description: '验证串', type: 'string'),
        new OA\Property(property: 'captcha', description: '验证码', type: 'string'),
    ]
)]
class PamBindMobileRequest extends Request
{

    protected bool $isValidate = false;

    public function getPassport(): string
    {
        return (string) $this->get('passport');
    }

    public function getCaptcha(): string
    {
        return (string) $this->get('passport');
    }

    public function getVerifyCode(): string
    {
        return (string) $this->get('verify_code');
    }

    public function attributes(): array
    {
        return [
            'passport'    => '通行证',
            'verify_code' => '验证串',
            'captcha'     => '验证码',
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
            'passport'    => [
                Rule::required(),
                Rule::mobile(),
            ],
            'captcha'     => [
                Rule::required(),
                Rule::numeric(),
            ],
            'verify_code' => [
                Rule::required(),
                Rule::string(),
            ],
        ];
    }
}
