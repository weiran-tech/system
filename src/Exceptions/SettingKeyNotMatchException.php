<?php

declare(strict_types = 1);

namespace Weiran\System\Exceptions;

use Weiran\Framework\Exceptions\BaseException;

class SettingKeyNotMatchException extends BaseException
{
    public function __construct($key = '')
    {
        $message = trans('weiran-system::util.exception.setting_key_not_match', [
            'key' => $key,
        ]);
        parent::__construct($message);
    }
}