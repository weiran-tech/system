<?php

declare(strict_types = 1);

namespace Weiran\System\Tests\Models;

use Illuminate\Auth\AuthenticationException;
use Weiran\Framework\Application\TestCase;
use Weiran\Framework\Exceptions\ApplicationException;
use Weiran\System\Classes\Traits\DbTrait;
use Weiran\System\Models\PamAccount;
use Weiran\System\Tests\Testing\TestingPam;
use Tymon\JWTAuth\JWTGuard;

class PamAccountTest extends TestCase
{
    use DbTrait;

    public function testJwtToken(): void
    {
        // assert user
        $user = TestingPam::randUser();
        /** @var JWTGuard $Jwt */
        $Jwt   = auth('jwt_web');
        $token = $Jwt->tokenById($user->id);

        try {
            if ($Jwt->setToken($token)->authenticate()) {
                $this->assertTrue(true);
            }
            else {
                $this->fail('use `jwt:secret` generate token');
            }
        } catch (AuthenticationException $e) {
            $this->fail($e->getMessage());
        }
    }

    /**
     * @throws ApplicationException
     */
    public function testType(): void
    {
        $mail = $this->faker()->email;
        $type = PamAccount::passportType($mail);
        $this->assertEquals('email', $type);
    }

    public function testExclude(): void
    {
        $exclude = TestingPam::exclude();
        $this->assertNotNull($exclude);
    }


    public function testPwdStrength(): void
    {
        $pwd = '123456';
        $this->assertCount(1, PamAccount::pwdStrength($pwd));
        $pwd = '123456abc';
        $this->assertCount(2, PamAccount::pwdStrength($pwd));
        $pwd = '123456ABC';
        $this->assertCount(2, PamAccount::pwdStrength($pwd));
        $pwd = '123456ABCabc';
        $this->assertCount(3, PamAccount::pwdStrength($pwd));
        $pwd = '123456ABCabc*';
        $this->assertCount(4, PamAccount::pwdStrength($pwd));
    }
}