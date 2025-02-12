<?php

declare(strict_types = 1);

namespace Weiran\System\Exceptions;

use Weiran\Framework\Exceptions\BaseException;

class SettingValueOutOfRangeException extends BaseException
{
    public function __construct($key = '')
    {
        $message = trans('weiran-system::util.exception.setting_value_out_of_range', [
            'key' => $key
        ]);
        parent::__construct($message);
    }
}