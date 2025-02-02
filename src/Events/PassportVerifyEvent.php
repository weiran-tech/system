<?php

declare(strict_types = 1);

namespace Weiran\System\Events;

/**
 * 发送验证码前的校验, 用于发送前的校验
 */
class PassportVerifyEvent
{
    /**
     * 通行证
     * @var string
     */
    public $passport;

    /**
     * 类型
     * @var string
     */
    public $type;

    public function __construct($passport, $type = '')
    {
        $this->passport = $passport;
        $this->type     = $type;
    }
}