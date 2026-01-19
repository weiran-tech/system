<?php

declare(strict_types = 1);

namespace Weiran\System\Http\Web\ApiV1\Auth;

use Weiran\Framework\Application\Request;
use Weiran\Framework\Validation\Rule;

class AuthConfirmedPasswordRequest extends Request
{
    public function attributes(): array
    {
        return [
            'password' => '密码',
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'password' => [
                Rule::required(),
                Rule::string(),
                Rule::simplePwd(),
                Rule::confirmed(),
                Rule::between(6, 20),
            ],
        ];
    }
}
