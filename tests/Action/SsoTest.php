<?php

declare(strict_types = 1);

namespace Weiran\System\Tests\Action;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use JWTAuth;
use Weiran\Framework\Application\TestCase;
use Weiran\System\Action\Sso;
use Weiran\System\Tests\Testing\TestingPam;

/**
 * 单点登录测试
 */
class SsoTest extends TestCase
{

    public function setUp(): void
    {
        parent::setUp();
        config('poppy.system.sso_group', [
            'app:' . Sso::GROUP_KICKED    => ['android', 'ios'],
            'web:' . Sso::GROUP_UNLIMITED => ['h5', 'webapp'],
        ]);
    }

    /**
     * 测试同时登录限制
     * @return void
     * @throws Exception
     */
    public function testDeviceNum(): void
    {
        $oldSsoType      = (string) sys_setting('py-system::pam.sso_type');
        $oldMaxDeviceNum = (int) (sys_setting('py-system::pam.sso_device_num') ?: 10);

        app('poppy.system.setting')->set('py-system::pam.sso_type', Sso::SSO_DEVICE_NUM);
        app('poppy.system.setting')->set('py-system::pam.sso_device_num', 3);

        $num  = 1;
        $Sso  = new Sso();
        $user = TestingPam::randUser();
        $Sso->banUser($user->id);

        $expired = [];
        $success = [];

        // 总共 4 台设备, 数量 3, 第一个失效
        while ($num <= 4) {
            $jwt        = JWTAuth::fromUser($user);
            $deviceType = 'android';
            if (!$expired) {
                $expired[] = [$jwt, $deviceType];
            }
            else {
                $success[] = [$jwt, $deviceType];
            }
            $deviceId = 'test-sso-all-' . $num;
            if (!$Sso->handle($user, $deviceId, $deviceType, $jwt)) {
                $this->fail((string) $Sso->getError());
            }
            $num++;
        }

        $this->runExpired($expired);
        $this->runSuccess($success);

        app('poppy.system.setting')->set('py-system::pam.sso_type', $oldSsoType);
        app('poppy.system.setting')->set('py-system::pam.sso_device_num', $oldMaxDeviceNum);

        $Sso->banUser($user->id);
    }


    /**
     * 测试设备登录限制
     * @return void
     * @throws Exception
     */
    public function testGroupUnlimited(): void
    {
        $oldSsoType = (string) sys_setting('py-system::pam.sso_type');

        app('poppy.system.setting')->set('py-system::pam.sso_type', Sso::SSO_GROUP);

        $num  = 1;
        $Sso  = new Sso();
        $user = TestingPam::randUser();
        $Sso->banUser($user->id);

        $success = [];
        while ($num <= 4) {
            $jwt        = JWTAuth::fromUser($user);
            $deviceType = 'h5';
            $success[]  = [$jwt, $deviceType];
            $deviceId   = 'test-sso-group-unlimited-' . $num;
            if (!$Sso->handle($user, $deviceId, $deviceType, $jwt)) {
                $this->fail((string) $Sso->getError());
            }
            $num++;
        }

        $this->runSuccess($success);

        app('poppy.system.setting')->set('py-system::pam.sso_type', $oldSsoType);

        $Sso->banUser($user->id);
    }

    /**
     * 测试单设备登录
     * @return void
     * @throws Exception
     */
    public function testGroupKicked(): void
    {
        $oldSsoType = (string) sys_setting('py-system::pam.sso_type');

        app('poppy.system.setting')->set('py-system::pam.sso_type', Sso::SSO_GROUP);

        $num  = 1;
        $Sso  = new Sso();
        $user = TestingPam::randUser();
        $Sso->banUser($user->id);

        $expired = [];
        $success = [];
        while ($num <= 6) {
            $jwt        = JWTAuth::fromUser($user);
            $deviceType = py_faker()->randomElement(['android', 'ios']);
            if ($num !== 6) {
                $expired[] = [$jwt, $deviceType];
            }
            else {
                $success[] = [$jwt, $deviceType];
            }
            $deviceId = 'test-sso-group-click-' . $num;
            if (!$Sso->handle($user, $deviceId, $deviceType, $jwt)) {
                $this->fail((string) $Sso->getError());
            }
            $num++;
        }

        $this->runExpired($expired);
        $this->runSuccess($success);

        app('poppy.system.setting')->set('py-system::pam.sso_type', $oldSsoType);

        $Sso->banUser($user->id);
    }

    /**
     * @throws GuzzleException
     */
    private function runAuth(string $jwt, string $os): void
    {
        $client = new Client();
        $this->outputVariables($os);
        $client->post(url('api_v1/system/auth/access'), [
            'headers'     => [
                'Authorization' => "Bearer {$jwt}",
                'x-os'          => $os,
            ],
            'form_params' => [
                '_py_secret' => env('PY_SECRET'),
            ],
        ]);

    }

    private function runExpired($expired): void
    {

        foreach ($expired as $item) {
            try {
                $this->runAuth($item[0], $item[1]);
                $this->fail('这里应该返回 401 错误, 不应该正确返回数据');
            } catch (Exception $e) {
                $this->assertEquals(401, $e->getCode());
            } catch (GuzzleException $e) {
                $this->fail($e->getMessage());
            }
        }

    }

    /**
     */
    private function runSuccess($success): void
    {

        foreach ($success as $item) {
            try {
                $this->runAuth($item[0], $item[1]);
            } catch (ClientException $e) {
                $this->outputVariables($item);
                $this->fail('client:' . $e->getMessage());
            } catch (GuzzleException $e) {
                $this->fail('guzzle:' . $e->getMessage());
            }
            $this->assertTrue(true, '这里应该正常请求');
        }
    }
}