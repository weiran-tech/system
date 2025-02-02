<?php

declare(strict_types = 1);

namespace Weiran\System\Tests\Base;

use InvalidArgumentException;
use Weiran\Framework\Application\TestCase;
use Weiran\Framework\Helper\UtilHelper;

class ConfigTest extends TestCase
{
    /**
     * 测试存在 Public Storage
     * @return void
     */
    public function testHasPublicStorage(): void
    {
        try {
            app('filesystem')->disk('public');
            $this->assertTrue(true);
        } catch (InvalidArgumentException $e) {
            $this->fail('disk `public` not exist, you need define `public` directory for local upload');
        }
    }


    /**
     * 测试验证码注册的开关
     * @return void
     */
    public function testSystemCaptchaRegister(): void
    {
        $captchaRegister = config('poppy.system.captcha_register');
        $this->assertIsBool($captchaRegister);
    }

    public function testTrans(): void
    {
        // 检测 auth.throttle 是否设置了语言
        $this->assertTrue(UtilHelper::hasChinese(trans('auth.throttle')));
    }
}