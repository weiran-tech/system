<?php

declare(strict_types = 1);

namespace Weiran\System\Tests\Action;

use Weiran\Framework\Application\TestCase;
use Weiran\Framework\Exceptions\ApplicationException;
use Weiran\System\Action\Verification;

class VerificationTest extends TestCase
{

    /**
     * @throws ApplicationException
     */
    public function testCaptcha(): void
    {
        $Verification = new Verification();
        $mobile       = $this->faker()->phoneNumber;
        if ($Verification->genCaptcha($mobile)) {
            $captcha = $Verification->getCaptcha();
            $this->assertTrue($Verification->checkCaptcha($mobile, $captcha));
        }
        else {
            $this->fail($Verification->getError()->getMessage());
        }

        $mobile = $this->faker()->phoneNumber;
        $Verification->genCaptcha($mobile, 5, 4);
        $captcha = $Verification->getCaptcha();
        $this->assertEquals(4, strlen($captcha));


        $mobile = $this->faker()->phoneNumber;
        $Verification->genCaptcha($mobile, 5, 4);
        $captcha = $Verification->getCaptcha();
        $this->assertEquals(4, strlen($captcha));
    }

    /**
     * 验证一次验证码
     */
    public function testOnceCode(): void
    {
        $Verification = new Verification();
        $hidden       = 'once-code';
        $onceCode     = $Verification->genOnceVerifyCode(5, $hidden);
        $Verification->verifyOnceCode($onceCode, false);
        $this->assertEquals($hidden, $Verification->getHidden());

        // 支持数组隐藏
        $hidden   = ['a', 'b'];
        $onceCode = $Verification->genOnceVerifyCode(5, $hidden);
        $Verification->verifyOnceCode($onceCode, false);
        $this->assertEquals($hidden, $Verification->getHidden());
    }

    /**
     * 验证存储的值
     */
    public function testWord(): void
    {
        $Verification = new Verification();
        $str          = 'once-code';
        $key          = '1-2-3';
        // 存在, 成功
        $Verification->saveWord($key, $str);
        $this->assertTrue($Verification->verifyWord($key, $str), $Verification->getError()->getMessage());

        // 不存在, 失败
        $Verification->removeWord($key);
        $this->assertFalse($Verification->verifyWord($key, $str), $Verification->getError()->getMessage());

        // 支持数组隐藏
        $str = ['a', 'b'];
        $Verification->saveWord($key, $str);
        $this->assertTrue($Verification->verifyWord($key, $str), $Verification->getError()->getMessage());

        $str = 428;
        // 存在, 成功
        $Verification->saveWord($key, $str);
        $this->assertTrue($Verification->verifyWord($key, $str), $Verification->getError()->getMessage());
        // 验证不匹配, 失败
        $this->assertFalse($Verification->verifyWord($key, $str + 1), $Verification->getError()->getMessage());
    }
}
