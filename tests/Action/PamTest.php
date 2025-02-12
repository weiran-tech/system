<?php

namespace Weiran\System\Tests\Action;

use Carbon\Carbon;
use Weiran\Framework\Application\TestCase;
use Weiran\Framework\Exceptions\ApplicationException;
use Weiran\System\Action\Pam;
use Weiran\System\Action\Verification;
use Weiran\System\Exceptions\SettingKeyNotMatchException;
use Weiran\System\Exceptions\SettingValueOutOfRangeException;
use Weiran\System\Models\PamAccount;
use Weiran\System\Tests\Testing\TestingPam;
use Throwable;

class PamTest extends TestCase
{

    /**
     * 验证码注册
     * @throws ApplicationException
     */
    public function testCaptchaLogin(): void
    {
        // 一个虚拟手机号
        $mobile = $this->faker()->phoneNumber;

        // 发送验证码
        $Verification = new Verification();
        if (!$Verification->genCaptcha($mobile)) {
            $this->fail($Verification->getError());
        }

        $Pam = new Pam();
        try {
            if ($Pam->captchaLogin($mobile, $Verification->getCaptcha(), 'user')) {
                $this->assertTrue(true);
            }
            else {
                $this->fail($Pam->getError());
            }
        } catch (Throwable $e) {
            $this->fail($e->getMessage());
        }
    }

    /**
     * 空密码注册
     * @throws ApplicationException
     */
    public function testRegisterWithEmptyPassword(): void
    {
        // 使用一个虚拟手机号注册, 在用手机号登录
        $mobile = $this->faker()->phoneNumber;

        $Pam = new Pam();
        try {
            if ($Pam->register($mobile)) {
                $this->assertTrue(true);
            }
            else {
                $this->fail($Pam->getError());
            }
        } catch (Throwable $e) {
            $this->fail($e->getMessage());
        }

        // 设置密码
        $pam      = $Pam->getPam();
        $password = $this->faker()->bothify('H%?#?#?#');
        if ($Pam->setPassword($pam, $password)) {
            $this->assertTrue(true);
            try {
                if (!$Pam->loginCheck($pam->mobile, $password)) {
                    $this->fail($Pam->getError());
                }
                else {
                    $this->assertTrue(true);
                }
            } catch (ApplicationException $e) {
                $this->fail($e->getMessage());
            }
        }
        else {
            $this->fail($Pam->getError());
        }
    }

    /**
     * @throws ApplicationException
     */
    public function testRegisterWithUsername(): void
    {
        $passport = $this->faker()->lexify('testing_' . Carbon::now()->format('Ymd') . '_????????');
        $password = $this->faker()->lexify('H#???#?????%');
        $Pam      = new Pam();
        try {
            if ($Pam->register($passport, $password)) {
                $this->assertTrue(true);
            }
            else {
                $this->fail($Pam->getError());
            }
        } catch (Throwable $e) {
            $this->fail($e->getMessage());
        }
    }


    /**
     * @throws ApplicationException
     */
    public function testRebind(): void
    {
        $pam    = TestingPam::randUser();
        $mobile = $this->faker()->phoneNumber;
        $Pam    = new Pam();
        if ($Pam->rebind($pam, $mobile)) {
            $this->assertTrue(true);
        }
        else {
            $this->fail($Pam->getError());
        }
    }


    /**
     * @throws SettingKeyNotMatchException
     * @throws SettingValueOutOfRangeException
     */
    public function testCheckPwdStrength(): void
    {
        $Pam  = new Pam();
        $type = 'user';
        $key  = "weiran-system::pam.{$type}_pwd_strength";
        $old  = sys_setting($key);
        app('poppy.system.setting')->set($key, array_keys(PamAccount::kvPwdStrength()));
        $this->assertFalse($Pam->checkPwdStrength($type, '123456'));
        $this->assertFalse($Pam->checkPwdStrength($type, '123456x'));
        $this->assertFalse($Pam->checkPwdStrength($type, '123456xX'));
        $this->assertFalse($Pam->checkPwdStrength($type, '123456*'));
        $this->assertFalse($Pam->checkPwdStrength($type, '123456*X'));
        $this->assertFalse($Pam->checkPwdStrength($type, '123456*x'));
        $this->assertFalse($Pam->checkPwdStrength($type, 'X*x'));
        $this->assertTrue($Pam->checkPwdStrength($type, 'X*x1'));
        app('poppy.system.setting')->set($key, $old);
    }
}