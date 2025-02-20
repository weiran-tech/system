<?php

declare(strict_types = 1);

namespace Weiran\System\Http\Request\Web\Validation;

use Weiran\Framework\Application\Request;
use Weiran\Framework\Validation\Rule;
use Weiran\System\Action\Verification;

class CaptchaSendRequest extends Request
{

    public function getPassport()
    {
        return $this->input('passport', '');
    }

    public function getType()
    {
        return $this->input('type', '');
    }

    public function attributes(): array
    {
        return [
            'passport' => '通行证',
            'type'     => '验证类型',
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
                Rule::string(),
            ],
            'type'     => [
                Rule::nullable(),
                Rule::in([
                    Verification::CAPTCHA_SEND_TYPE_EXIST,
                    Verification::CAPTCHA_SEND_TYPE_NO_EXIST,
                ]),
            ],
        ];
    }
}
