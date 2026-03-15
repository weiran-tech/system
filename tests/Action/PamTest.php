<?php

declare(strict_types = 1);

namespace Weiran\System\Tests\Action;

use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use DB;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Random\RandomException;
use ReflectionClass;
use Throwable;
use Weiran\Framework\Application\TestCase;
use Weiran\Framework\Exceptions\ApplicationException;
use Weiran\Framework\Support\Exceptions\TestingException;
use Weiran\System\Action\Pam;
use Weiran\System\Action\Verification;
use Weiran\System\Events\LoginBannedEvent;
use Weiran\System\Events\LoginFailedEvent;
use Weiran\System\Events\LoginSuccessEvent;
use Weiran\System\Events\PamDisableEvent;
use Weiran\System\Events\PamEnableEvent;
use Weiran\System\Events\PamLogoutEvent;
use Weiran\System\Events\PamPasswordModifiedEvent;
use Weiran\System\Events\PamRebindEvent;
use Weiran\System\Events\PamRegisteredEvent;
use Weiran\System\Models\PamAccount;
use Weiran\System\Models\SysConfig;
use Weiran\System\Tests\Testing\TestingPam;
use Weiran\System\Tests\Testing\TestingRole;

#[CoversClass(Pam::class)]
class PamTest extends TestCase
{
    /**
     * @throws Throwable
     * @throws RandomException
     */
    public function testCaptchaLoginSuccess(): void
    {
        Event::fake();
        $pamAction = new Pam();

        $mobile       = $this->faker()->phoneNumber;
        $verification = new Verification();
        $verification->genCaptcha($mobile);
        $captcha = $verification->getCaptcha();

        $pam = new Pam();
        $pam->register($mobile);
        $accountId = $pam->getPam()->id;

        $result = $pamAction->captchaLogin($mobile, $captcha, 'web');
        $this->assertTrue($result);
        $this->assertEquals($accountId, $pamAction->getPam()->id);
        $this->assertFalse($pamAction->getIsRegister());

        Event::assertDispatched(LoginBannedEvent::class);
        Event::assertDispatched(LoginSuccessEvent::class);
    }

    /**
     * @throws Throwable
     * @throws RandomException
     */
    public function testCaptchaLoginWithAutoRegister(): void
    {
        Event::fake();
        config(['weiran.system.captcha_register' => true]);
        $pamAction = new Pam();

        $mobile       = $this->faker()->phoneNumber;
        $verification = new Verification();
        $verification->genCaptcha($mobile);
        $captcha = $verification->getCaptcha();

        $result = $pamAction->captchaLogin($mobile, $captcha, 'web');
        $this->assertTrue($result);
        $this->assertTrue($pamAction->getIsRegister());

        Event::assertDispatched(PamRegisteredEvent::class);
    }

    /**
     * @throws Throwable
     * @throws RandomException
     */
    public function testCaptchaLoginWhenAutoRegisterDisabled(): void
    {
        config(['weiran.system.captcha_register' => false]);
        $pamAction = new Pam();

        $mobile       = $this->faker()->phoneNumber;
        $verification = new Verification();
        $verification->genCaptcha($mobile);
        $captcha = $verification->getCaptcha();

        $result = $pamAction->captchaLogin($mobile, $captcha, 'web');
        $this->assertFalse($result);
        $this->assertStringContainsString('账号不存在', (string) $pamAction->getError());
    }

    /**
     * @throws Throwable
     * @throws RandomException
     */
    public function testCaptchaLoginForBackendWithNoAutoRegister(): void
    {
        config(['weiran.system.captcha_register' => true]);
        $pamAction = new Pam();

        $mobile       = $this->faker()->phoneNumber;
        $verification = new Verification();
        $verification->genCaptcha($mobile);
        $captcha = $verification->getCaptcha();

        $result = $pamAction->captchaLogin($mobile, $captcha, 'backend');
        $this->assertFalse($result);
        $this->assertStringContainsString('不允许自动注册', (string) $pamAction->getError());
    }

    /**
     * @throws Throwable
     */
    public function testCaptchaLoginWhenInvalidCaptcha(): void
    {
        $pamAction = new Pam();
        $mobile    = $this->faker()->phoneNumber;

        $result = $pamAction->captchaLogin($mobile, '000000', 'web');
        $this->assertFalse($result);
        $this->assertStringContainsString('验证', (string) $pamAction->getError());
    }

    /**
     * @throws Throwable
     * @throws RandomException
     */
    public function testCaptchaLoginWhenAccountDisabled(): void
    {
        Event::fake();
        $pamAction = new Pam();

        $mobile       = $this->faker()->phoneNumber;
        $verification = new Verification();
        $verification->genCaptcha($mobile);
        $captcha = $verification->getCaptcha();

        $pam = new Pam();
        $pam->register($mobile);
        $account = $pam->getPam();
        $pam->disable($account->id, Carbon::now()->addDay()->toDateTimeString(), 'Test disable');

        $result = $pamAction->captchaLogin($mobile, $captcha, 'web');
        $this->assertFalse($result);
        $this->assertStringContainsString('封禁', (string) $pamAction->getError());
    }

    /**
     * @throws TestingException
     * @throws RandomException
     */
    public function testBeCaptchaLoginSuccess(): void
    {
        Event::fake();
        $pamAction = new Pam();

        $backendUser = TestingPam::backend();
        $mobile      = $backendUser->mobile;

        $verification = new Verification();
        $verification->genCaptcha($mobile);
        $captcha = $verification->getCaptcha();

        $result = $pamAction->beCaptchaLogin($mobile, $captcha);
        $this->assertTrue($result);
        $this->assertEquals($backendUser->id, $pamAction->getPam()->id);

        Event::assertDispatched(LoginBannedEvent::class);
        Event::assertDispatched(LoginSuccessEvent::class);
    }

    /**
     * @throws TestingException
     */
    public function testBeCaptchaLoginWhenInvalidCaptcha(): void
    {
        $pamAction   = new Pam();
        $backendUser = TestingPam::backend();
        $mobile      = $backendUser->mobile;

        $result = $pamAction->beCaptchaLogin($mobile, '000000');
        $this->assertFalse($result);
        $this->assertStringContainsString('验证', (string) $pamAction->getError());
    }

    public function testSetParentIdAndGetIsRegister(): void
    {
        $pamAction = new Pam();
        $parentId  = 123;
        $pamAction->setParentId($parentId);

        $reflection = new ReflectionClass($pamAction);
        $property   = $reflection->getProperty('parentId');
        $property->setAccessible(true);
        $this->assertEquals($parentId, $property->getValue($pamAction));

        $this->assertFalse($pamAction->getIsRegister());
    }

    /**
     * @throws Throwable
     */
    public function testRegisterWithUsernameSuccess(): void
    {
        Event::fake();
        DB::beginTransaction();
        $pamAction = new Pam();

        $username = 'testuser_' . strtolower(Str::random(6));
        $password = 'password123';

        $result = $pamAction->register($username, $password);
        $this->assertTrue($result);

        $pam = $pamAction->getPam();
        $this->assertNotNull($pam);
        $this->assertEquals($username, $pam->username);
        $this->assertNotNull($pam->password);

        Event::assertDispatched(PamRegisteredEvent::class);

        DB::rollBack();
    }

    /**
     * @throws Throwable
     */
    public function testRegisterWithMobileSuccess(): void
    {
        Event::fake();
        DB::beginTransaction();
        $pamAction = new Pam();

        $mobile = $this->faker()->phoneNumber;

        $result = $pamAction->register($mobile);
        $this->assertTrue($result);

        $pam = $pamAction->getPam();
        $this->assertNotNull($pam);
        $this->assertEquals('86-' . $mobile, $pam->mobile);

        Event::assertDispatched(PamRegisteredEvent::class);

        DB::rollBack();
    }

    /**
     * @throws Throwable
     */
    public function testRegisterWithEmailSuccess(): void
    {
        Event::fake();
        DB::beginTransaction();
        $pamAction = new Pam();

        $email = $this->faker()->unique()->safeEmail;

        $result = $pamAction->register($email);
        $this->assertTrue($result);

        $pam = $pamAction->getPam();
        $this->assertNotNull($pam);
        $this->assertEquals($email, $pam->email);

        Event::assertDispatched(PamRegisteredEvent::class);

        DB::rollBack();
    }

    /**
     * @throws Throwable
     */
    public function testRegisterWhenDuplicateUsername(): void
    {
        DB::beginTransaction();
        $pam = new Pam();

        $username = 'testuser_' . Str::random(6);
        $pam->register($username, 'password123');

        try {
            $pam->register($username, 'password123');
        }
        catch (Exception $e) {
            $this->assertStringContainsString('已存在', $e->getMessage());
        }

        DB::rollBack();
    }

    /**
     * @throws Throwable
     */
    public function testRegisterWhenUsernameHasSpaces(): void
    {
        $pam      = new Pam();
        $username = 'test user-' . Str::random(6);

        $result = $pam->register($username, 'password123');
        $this->assertFalse($result);
        $this->assertStringContainsString('空格', (string) $pam->getError());
    }

    /**
     * @throws Throwable
     * @throws ApplicationException
     */
    public function testRegisterForSubUserWithoutColon(): void
    {
        $pam        = new Pam();
        $parentUser = TestingPam::randParentUser();
        $pam->setParentId($parentUser->id);

        $subUsername = 'subuser' . Str::random(6);

        $result = $pam->register($subUsername, 'password123');
        $this->assertFalse($result);
        $this->assertStringContainsString(':', (string) $pam->getError());
    }

    /**
     * @throws Throwable
     * @throws ApplicationException
     */
    public function testRegisterForSubUserWithColonSuccess(): void
    {
        Event::fake();
        DB::beginTransaction();
        $pam = new Pam();

        $parentUser = TestingPam::randParentUser();
        $pam->setParentId($parentUser->id);

        $subUsername = $parentUser->username . ':' . 'sub' . Str::random(6);

        $result = $pam->register($subUsername, 'password123');
        $this->assertTrue($result);

        $pamAccount = $pam->getPam();
        $this->assertEquals($parentUser->id, $pamAccount->parent_id);

        Event::assertDispatched(PamRegisteredEvent::class);

        DB::rollBack();
    }

    /**
     * @throws Throwable
     */
    public function testRegisterWhenInvalidPassword(): void
    {
        $pam      = new Pam();
        $username = 'test_reg_invalid_pwd_' . Str::random(6);

        $result = $pam->register($username, '123');
        $this->assertFalse($result);

        $result = $pam->register($username, str_repeat('a', 25));
        $this->assertFalse($result);
    }

    /**
     * @throws Throwable
     */
    public function testRegisterWhenNonExistentRole(): void
    {
        DB::beginTransaction();
        $pam = new Pam();

        $username = 'testuser_' . Str::random(6);

        $result = $pam->register($username, 'password123', 'non_existent_role');
        $this->assertFalse($result);
        $this->assertStringContainsString('角色', (string) $pam->getError());

        DB::rollBack();
    }

    /**
     * @throws ApplicationException
     * @throws ContainerExceptionInterface
     * @throws ExpectationFailedException
     * @throws NotFoundExceptionInterface
     * @throws AssertionFailedError
     */
    public function testLoginCheckSuccess(): void
    {
        Event::fake();
        $pam = new Pam();

        $user     = TestingPam::randUser();
        $passport = $user->mobile ?: $user->username;
        $password = 'pass?woArd123**';

        $pam->setPam($user);
        if (!$pam->setPassword($user, $password)) {
            $this->fail($pam->getError()->getMessage());
        }

        $result = $pam->loginCheck($passport, $password);
        $this->assertTrue($result);
        $this->assertEquals($user->id, $pam->getPam()->id);

        Event::assertDispatched(LoginBannedEvent::class);
        Event::assertDispatched(LoginSuccessEvent::class);

        Auth::logout();
    }

    /**
     * @throws ApplicationException
     */
    public function testLoginCheckWhenFail(): void
    {
        Event::fake();
        $pam = new Pam();

        $user     = TestingPam::randUser();
        $passport = $user->mobile ?: $user->username;

        $result = $pam->loginCheck($passport, 'wrongpassword');
        $this->assertFalse($result);

        Event::assertDispatched(LoginFailedEvent::class);
    }

    /**
     * @throws ApplicationException
     */
    public function testLoginCheckWhenAccountDisabled(): void
    {
        $pam = new Pam();

        $user     = TestingPam::randUser();
        $passport = $user->mobile ?: $user->username;
        $password = 'password123';

        $pam->setPam($user);
        $pam->setPassword($user, $password);
        $pam->disable($user->id, Carbon::now()->addDay()->toDateTimeString(), 'Test');

        $result = $pam->loginCheck($passport, $password);
        $this->assertFalse($result);
        $this->assertStringContainsString('封禁', (string) $pam->getError());

        Auth::logout();
    }

    public function testSetPasswordSuccess(): void
    {
        Event::fake();
        $pam = new Pam();

        $user        = TestingPam::randUser();
        $newPassword = 'newpass123';

        $result = $pam->setPassword($user, $newPassword);
        $this->assertTrue($result);

        $user->refresh();
        $this->assertNotNull($user->password);

        Event::assertDispatched(PamPasswordModifiedEvent::class);
    }

    public function testSetPasswordWhenInvalid(): void
    {
        $pam  = new Pam();
        $user = TestingPam::randUser();

        $result = $pam->setPassword($user, '123');
        $this->assertFalse($result);

        $result = $pam->setPassword($user, str_repeat('a', 25));
        $this->assertFalse($result);
    }

    /**
     * @throws ApplicationException
     * @throws AssertionFailedError
     * @throws ExpectationFailedException
     * @throws ModelNotFoundException
     * @throws InvalidArgumentException
     */
    public function testClearMobile(): void
    {
        $pam         = new Pam();
        $backendUser = TestingPam::backend();
        $pam->setPam($backendUser);

        if (!$result = $pam->clearMobile($backendUser->id)) {
            $this->fail($pam->getError()->getMessage());
        }
        $this->assertTrue($result);

        $backendUser->refresh();
        $this->assertStringStartsWith(PamAccount::BACKEND_MOBILE_PREFIX, $backendUser->mobile);
    }

    public function testSetMobile(): void
    {
        $pam         = new Pam();
        $backendUser = TestingPam::randBackend();
        $newMobile   = $this->faker()->phoneNumber;
        $pam->setPam($backendUser);

        $result = $pam->setMobile($backendUser, $newMobile);
        $this->assertTrue($result);

        $backendUser->refresh();
        $expectedMobile = PamAccount::BACKEND_MOBILE_PREFIX . $newMobile;
        $this->assertEquals($expectedMobile, $backendUser->mobile);
    }

    public function testSetNote(): void
    {
        $pam  = new Pam();
        $user = TestingPam::randUser();
        $note = 'Test note ' . Str::random(10);

        $pam->setNote($user, $note);

        $user->refresh();
        $this->assertEquals($note, $user->note);
    }

    public function testSetRoles(): void
    {
        $pam   = new Pam();
        $user  = TestingPam::randUser();
        $roles = TestingRole::randUser();

        $result = $pam->setRoles($user, [$roles->id]);
        $this->assertTrue($result);

        $user->refresh();
        $this->assertTrue($user->roles->contains($roles->id));
    }

    public function testPassportDataWithRequest(): void
    {
        $pam      = new Pam();
        $passport = 'test@example.com';
        $password = 'password123';

        $request = new Request([
            'passport' => $passport,
            'password' => $password,
        ]);

        $result = $pam->passportData($request);
        $this->assertEquals($passport, $result['email']);
        $this->assertEquals($password, $result['password']);
    }

    public function testPassportDataWithArray(): void
    {
        $pam  = new Pam();
        $data = [
            'mobile'   => $this->faker()->phoneNumber,
            'password' => 'password123',
        ];

        $result = $pam->passportData($data);
        $this->assertEquals($data['mobile'], $result['mobile']);
        $this->assertEquals($data['password'], $result['password']);
    }

    public function testPassportDataWithFallback(): void
    {
        $pam  = new Pam();
        $data = [
            'username' => 'testuser',
            'password' => 'password123',
        ];

        $result = $pam->passportData($data);
        $this->assertEquals('testuser', $result['username']);
    }

    /**
     * @throws Throwable
     */
    public function testRebindSuccess(): void
    {
        Event::fake();
        DB::beginTransaction();
        $pam = new Pam();

        $oldMobile = $this->faker()->phoneNumber;
        $newMobile = $this->faker()->phoneNumber;

        $pam->register($oldMobile);
        $oldPam = $pam->getPam();

        $result = $pam->rebind($oldMobile, $newMobile);
        $this->assertTrue($result);

        $oldPam->refresh();
        $this->assertEquals('86-' . $newMobile, $oldPam->mobile);

        Event::assertDispatched(PamRebindEvent::class);

        DB::rollBack();
    }

    /**
     * @throws Throwable
     */
    public function testRebindWhenAlreadyExists(): void
    {
        $pam     = new Pam();
        $mobile1 = $this->faker()->phoneNumber;
        $mobile2 = $this->faker()->phoneNumber;

        $pam->register($mobile1);
        $pam->register($mobile2);

        $result = $pam->rebind($mobile1, $mobile2);
        $this->assertFalse($result);
        $this->assertStringContainsString('已存在', (string) $pam->getError());
    }

    public function testRebindWhenOldNotFound(): void
    {
        $pam    = new Pam();
        $result = $pam->rebind('nonexistent', $this->faker()->phoneNumber);
        $this->assertFalse($result);
        $this->assertStringContainsString('不存在', (string) $pam->getError());
    }

    /**
     * @throws TestingException
     */
    public function testDisableSuccess(): void
    {
        Event::fake();
        $pam = new Pam();
        $pam->setPam(TestingPam::backend());

        $user      = TestingPam::randUser();
        $disableTo = Carbon::now()->addDay()->toDateTimeString();
        $reason    = 'Test disable reason';

        $result = $pam->disable($user->id, $disableTo, $reason);
        $this->assertTrue($result);

        $user->refresh();
        $this->assertEquals(SysConfig::DISABLE, $user->is_enable);
        $this->assertEquals($reason, $user->disable_reason);

        Event::assertDispatched(PamDisableEvent::class);
    }

    /**
     * @throws TestingException
     */
    public function testDisableWhenAlreadyDisabled(): void
    {
        $pam = new Pam();
        $pam->setPam(TestingPam::backend());
        $user      = TestingPam::randUser();
        $disableTo = Carbon::now()->addDay()->toDateTimeString();

        $pam->disable($user->id, $disableTo, 'First disable');

        $result = $pam->disable($user->id, $disableTo, 'Second disable');
        $this->assertFalse($result);
        $this->assertStringContainsString('已禁用', (string) $pam->getError());
    }

    public function testDisableWhenInvalidDateFormat(): void
    {
        $pam  = new Pam();
        $user = TestingPam::randUser();

        $result = $pam->disable($user->id, 'invalid-date', 'Test');
        $this->assertFalse($result);
    }

    public function testDisableWhenPastDate(): void
    {
        $pam      = new Pam();
        $user     = TestingPam::randUser();
        $pastDate = Carbon::now()->subDay()->toDateTimeString();

        $result = $pam->disable($user->id, $pastDate, 'Test');
        $this->assertFalse($result);
        $this->assertStringContainsString('大于当前日期', (string) $pam->getError());
    }

    /**
     * @throws ApplicationException
     * @throws ExpectationFailedException
     * @throws TestingException
     * @throws InvalidFormatException
     */
    public function testEnableSuccess(): void
    {
        Event::fake();
        $pam = new Pam();
        $pam->setPam(TestingPam::backend());
        $user      = TestingPam::randUser();
        $disableTo = Carbon::now()->addDay()->toDateTimeString();

        $pam->disable($user->id, $disableTo, 'Test disable');

        $result = $pam->enable($user->id, 'Test enable');
        $this->assertTrue($result);

        $user->refresh();
        $this->assertEquals(SysConfig::ENABLE, $user->is_enable);

        Event::assertDispatched(PamEnableEvent::class);
    }

    public function testEnableWhenUserNotFound(): void
    {
        $pam    = new Pam();
        $result = $pam->enable(999999, 'Test');
        $this->assertFalse($result);
        $this->assertStringContainsString('不存在', (string) $pam->getError());
    }

    public function testEnableWhenAlreadyEnabled(): void
    {
        $pam  = new Pam();
        $user = TestingPam::randEnabledUser();
        if ($user->is_enable === SysConfig::ENABLE) {
            $result = $pam->enable($user->id, 'Test');
            $this->assertFalse($result);
            $this->assertStringContainsString('启用状态', (string) $pam->getError());
        }
    }

    /**
     * @throws ApplicationException
     * @throws AssertionFailedError
     * @throws ExpectationFailedException
     * @throws InvalidFormatException
     * @throws TestingException
     * @throws Throwable
     */
    public function testAutoEnable(): void
    {
        Event::fake();
        $pam    = new Pam();
        $mobile = $this->faker()->phoneNumber;
        $pam->register($mobile);
        $currentPam = $pam->getPam();
        $pam->setPam(TestingPam::backend());
        $pastDate = Carbon::now()->addSeconds(1)->toDateTimeString();

        if (!$pam->disable($currentPam->id, $pastDate, 'Test')) {
            $this->fail($pam->getError()->getMessage());
        }

        sleep(2);
        $result = $pam->autoEnable();
        $this->assertTrue($result);

        $pamAccount = $pam->getPam();
        $pamAccount->refresh();
        $this->assertEquals(SysConfig::ENABLE, $pamAccount->is_enable);

        Event::assertDispatched(PamEnableEvent::class);
    }

    /**
     * @throws Throwable
     */
    public function testLogout(): void
    {
        Event::fake();
        $pam = new Pam();

        $user = TestingPam::randUser();
        $pam->setPam($user);

        $pam->logout();

        Event::assertDispatched(PamLogoutEvent::class);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testClearLogWithDays(): void
    {
        sys_setting_set('weiran-system::log.days', '30');
        $pam = new Pam();

        $result = $pam->clearLog();
        $this->assertTrue($result);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ExpectationFailedException
     */
    public function testClearLogWithForever(): void
    {
        sys_setting_set('weiran-system::log.days', 'forever');
        $pam = new Pam();

        $result = $pam->clearLog();
        $this->assertTrue($result);
    }

    public function testChangePasswordSuccess(): void
    {
        Event::fake();
        $pam = new Pam();

        $user        = TestingPam::randUser();
        $oldPassword = 'password123';
        $newPassword = 'newpass456';

        $pam->setPam($user);
        $pam->setPassword($user, $oldPassword);

        $result = $pam->changePassword($oldPassword, $newPassword);
        $this->assertTrue($result);

        Event::assertDispatched(PamPasswordModifiedEvent::class);
    }

    public function testChangePasswordWhenSame(): void
    {
        $pam      = new Pam();
        $user     = TestingPam::randUser();
        $password = 'password123';

        $pam->setPam($user);
        $pam->setPassword($user, $password);

        $result = $pam->changePassword($password, $password);
        $this->assertFalse($result);
        $this->assertStringContainsString('不能相同', (string) $pam->getError());
    }

    public function testChangePasswordWhenWrongOldPassword(): void
    {
        $pam         = new Pam();
        $user        = TestingPam::randUser();
        $oldPassword = 'password123';
        $newPassword = 'newpass456';

        $pam->setPam($user);
        $pam->setPassword($user, $oldPassword);

        $result = $pam->changePassword('wrongpassword', $newPassword);
        $this->assertFalse($result);
        $this->assertStringContainsString('旧密码不正确', (string) $pam->getError());
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testCheckPwdStrengthSufficient(): void
    {
        sys_setting_set('weiran-system::pam.user_pwd_strength', ['number', 'char']);
        $pam = new Pam();

        $password = 'abc123';
        $result   = $pam->checkPwdStrength(PamAccount::TYPE_USER, $password);
        $this->assertTrue($result);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ExpectationFailedException
     * @throws NotFoundExceptionInterface
     */
    public function testCheckPwdStrengthInsufficient(): void
    {
        sys_setting_set('weiran-system::pam.user_pwd_strength', ['number', 'char', 'special']);
        $pam = new Pam();

        $password = 'abc123';
        $result   = $pam->checkPwdStrength(PamAccount::TYPE_USER, $password);
        $this->assertFalse($result);
        $this->assertStringContainsString('密码强度不足', (string) $pam->getError());
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testCheckPwdStrengthWithNoRequirement(): void
    {
        sys_setting_set('weiran-system::pam.user_pwd_strength', []);
        $pam = new Pam();

        $password = 'abc';
        $result   = $pam->checkPwdStrength(PamAccount::TYPE_USER, $password);
        $this->assertTrue($result);
    }

    public function testCheckIsEnableWhenEnabled(): void
    {
        $pam  = new Pam();
        $user = TestingPam::randEnabledUser();

        $result = $pam->checkIsEnable($user);
        $this->assertTrue($result);
    }

    public function testCheckIsEnableWhenDisabled(): void
    {
        $pam = new Pam();
        $pam->setPam(TestingPam::backend());
        $user      = TestingPam::randUser();
        $disableTo = Carbon::now()->addDay()->toDateTimeString();

        if (!$pam->disable($user->id, $disableTo, 'Test disable')) {
            $this->fail($pam->getError()->getMessage());
        }

        $user->refresh();
        $result = $pam->checkIsEnable($user);
        $this->assertFalse($result);
        $this->assertStringContainsString('封禁', (string) $pam->getError());
    }

    /**
     * @throws ApplicationException
     * @throws AssertionFailedError
     * @throws ExpectationFailedException
     * @throws InvalidFormatException
     * @throws TestingException
     */
    public function testCheckIsEnableAutoEnableWhenExpired(): void
    {
        Event::fake();
        $pam = new Pam();
        $pam->setPam(TestingPam::backend());
        $user     = TestingPam::randEnabledUser();
        $pastDate = Carbon::now()->addSecond()->toDateTimeString();

        if (!$pam->disable($user->id, $pastDate, 'Test disable')) {
            $this->fail($pam->getError()->getMessage());
        }

        sleep(2);

        $result = $pam->checkIsEnable($user);
        $this->assertTrue($result);

        $user->refresh();

        $this->assertEquals(SysConfig::ENABLE, $user->is_enable);

        Event::assertDispatched(PamEnableEvent::class);
    }
}
