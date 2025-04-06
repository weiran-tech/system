<?php

declare(strict_types = 1);

namespace Weiran\System\Http\Request\Web\Validation;

use Weiran\Framework\Application\Request;
use Weiran\Framework\Validation\Rule;

class CaptchaVerifyRequest extends Request
{

    public function getPassport(): string
    {
        return $this->input('passport', '');
    }

    public function getCaptcha(): string
    {
        return $this->input('captcha', '');
    }

    public function getExpireMin(): int
    {
        return (int) $this->input('expire_min', 10);
    }

    public function attributes(): array
    {
        return [
            'passport' => '通行证',
            'captcha'  => '验证码',
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
            'passport'   => [
                Rule::required(),
                Rule::string(),
            ],
            'captcha'    => [
                Rule::required(),
                Rule::string(),
            ],
            'expire_min' => [
                Rule::nullable(),
                Rule::numeric(),
                Rule::between(1, 60),
            ],
        ];
    }
}
