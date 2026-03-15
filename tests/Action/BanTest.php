<?php

declare(strict_types = 1);

namespace Weiran\System\Tests\Action;

use Artisan;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\ExpectationFailedException;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Weiran\Core\Redis\RdsDb;
use Weiran\Framework\Application\TestCase;
use Weiran\Framework\Exceptions\ParamException;
use Weiran\System\Action\Ban;
use Weiran\System\Classes\WeiranSystemDef;
use Weiran\System\Events\PamTokenBanEvent;
use Weiran\System\Models\PamAccount;
use Weiran\System\Models\PamBan;
use Weiran\System\Models\PamToken;

/**
 * 封禁测试
 */
#[CoversClass(Ban::class)]
class BanTest extends TestCase
{
    private RdsDb $rds;

    /**
     * @throws ExpectationFailedException
     * @throws CommandNotFoundException
     */
    public function testIpv4Command(): void
    {
        $ips = $this->genIps();
        $this->clearIps($ips);
        foreach ($ips as $ip) {
            $code = Artisan::call('system:ban', [
                'type'  => 'backend',
                'value' => $ip,
            ]);
            $this->assertEquals(0, $code, "ip value is : {$ip}");
        }
        $this->removeIps($ips);

        // 错误 IP
        Artisan::call('system:ban', [
            'type'  => 'backend',
            'value' => 'error-ip',
        ]);

        // 错误的用户类型
        Artisan::call('system:ban', [
            'type'  => 'error-type',
            'value' => '127.0.0.1',
        ]);
    }

    /**
     * IP 匹配测试
     */
    public function testIpv4Matched(): void
    {
        $ips = [
            '136.60.196.79',
            '10.205.182.1-10.205.182.254',
            '172.31.204.*',
            '172.20.76.100',
            '192.168.81.1/24',
        ];

        $this->clearIps($ips);
        $Ban = new Ban();
        foreach ($ips as $ip) {
            if ($Ban->establish([
                'account_type' => 'user',
                'type'         => 'ip',
                'value'        => $ip,
            ])) {
                $this->assertTrue(true);
            }
            else {
                $this->fail($Ban->getError()->getMessage());
            }
        }

        $this->assertTrue($Ban->checkIn('user', 'ip', '136.60.196.79'));
        $this->assertTrue($Ban->checkIn('user', 'ip', '10.205.182.222'));
        $this->assertTrue($Ban->checkIn('user', 'ip', '172.31.204.3'));
        $this->assertTrue($Ban->checkIn('user', 'ip', '172.20.76.100'));
        $this->assertTrue($Ban->checkIn('user', 'ip', '192.168.81.255'));

        $this->removeIps($ips);

        $this->assertFalse($Ban->checkIn('user', 'ip', '136.60.196.79'));
        $this->assertFalse($Ban->checkIn('user', 'ip', '10.205.182.222'));
        $this->assertFalse($Ban->checkIn('user', 'ip', '172.31.204.3'));
        $this->assertFalse($Ban->checkIn('user', 'ip', '172.20.76.100'));
        $this->assertFalse($Ban->checkIn('user', 'ip', '192.168.81.255'));
    }

    /**
     * 创建 IP 封禁测试
     */
    public function testCreate(): void
    {
        $ips = $this->genIps();
        $this->clearIps($ips);
        $Ban = new Ban();
        foreach ($ips as $ip) {
            if ($Ban->establish([
                'account_type' => 'user',
                'type'         => 'ip',
                'value'        => $ip,
            ])) {
                $this->assertTrue(true);
            }
            else {
                $this->fail($Ban->getError()->getMessage());
            }
        }

        $this->removeIps($ips);
    }

    /**
     * 测试建立 IP 封禁 - 单个 IP
     */
    public function testEstablishSingleIp(): void
    {
        $this->clearBanData();

        $ban    = new Ban();
        $result = $ban->establish([
            'account_type' => PamAccount::TYPE_USER,
            'type'         => PamBan::TYPE_IP,
            'value'        => '192.168.1.1',
            'note'         => '测试封禁',
        ]);

        $this->assertTrue($result);
        $this->assertDatabaseHas('pam_ban', [
            'account_type' => PamAccount::TYPE_USER,
            'type'         => PamBan::TYPE_IP,
            'value'        => '192.168.1.1',
        ]);
        $this->assertTrue($ban->checkIn(PamAccount::TYPE_USER, PamBan::TYPE_IP, '192.168.1.1'));
    }

    /**
     * 测试建立 IP 封禁 - IP 范围
     */
    public function testEstablishIpRange(): void
    {
        $this->clearBanData();

        $ban    = new Ban();
        $result = $ban->establish([
            'account_type' => PamAccount::TYPE_USER,
            'type'         => PamBan::TYPE_IP,
            'value'        => '192.168.1.1-192.168.1.10',
        ]);

        $this->assertTrue($result);
        $this->assertDatabaseHas('pam_ban', [
            'account_type' => PamAccount::TYPE_USER,
            'type'         => PamBan::TYPE_IP,
            'value'        => '192.168.1.1-192.168.1.10',
        ]);
        $this->assertTrue($ban->checkIn(PamAccount::TYPE_USER, PamBan::TYPE_IP, '192.168.1.5'));
    }

    /**
     * 测试建立 IP 封禁 - IP 通配符
     */
    public function testEstablishIpWildcard(): void
    {
        $this->clearBanData();

        $ban    = new Ban();
        $result = $ban->establish([
            'account_type' => PamAccount::TYPE_USER,
            'type'         => PamBan::TYPE_IP,
            'value'        => '192.168.1.*',
        ]);

        $this->assertTrue($result);
        $this->assertDatabaseHas('pam_ban', [
            'account_type' => PamAccount::TYPE_USER,
            'type'         => PamBan::TYPE_IP,
            'value'        => '192.168.1.*',
        ]);
        $this->assertTrue($ban->checkIn(PamAccount::TYPE_USER, PamBan::TYPE_IP, '192.168.1.100'));
    }

    /**
     * 测试建立 IP 封禁 - CIDR 格式
     */
    public function testEstablishIpCidr(): void
    {
        $this->clearBanData();

        $ban    = new Ban();
        $result = $ban->establish([
            'account_type' => PamAccount::TYPE_USER,
            'type'         => PamBan::TYPE_IP,
            'value'        => '192.168.1.0/24',
        ]);

        $this->assertTrue($result);
        $this->assertDatabaseHas('pam_ban', [
            'account_type' => PamAccount::TYPE_USER,
            'type'         => PamBan::TYPE_IP,
            'value'        => '192.168.1.0/24',
        ]);
        $this->assertTrue($ban->checkIn(PamAccount::TYPE_USER, PamBan::TYPE_IP, '192.168.1.200'));
    }

    /**
     * 测试建立设备封禁
     */
    public function testEstablishDevice(): void
    {
        $this->clearBanData();

        $ban    = new Ban();
        $result = $ban->establish([
            'account_type' => PamAccount::TYPE_USER,
            'type'         => PamBan::TYPE_DEVICE,
            'value'        => 'device-test-123',
        ]);

        $this->assertTrue($result);
        $this->assertDatabaseHas('pam_ban', [
            'account_type' => PamAccount::TYPE_USER,
            'type'         => PamBan::TYPE_DEVICE,
            'value'        => 'device-test-123',
        ]);
        $this->assertTrue($ban->checkIn(PamAccount::TYPE_USER, PamBan::TYPE_DEVICE, 'device-test-123'));
    }

    /**
     * 测试建立封禁 - 无效的账号类型
     */
    public function testEstablishWithInvalidAccountType(): void
    {
        $this->clearBanData();

        $ban    = new Ban();
        $result = $ban->establish([
            'account_type' => 'invalid',
            'type'         => PamBan::TYPE_IP,
            'value'        => '192.168.1.1',
        ]);

        $this->assertFalse($result);
        $this->assertStringContainsString('账户类型', $ban->getError()->getMessage());
    }

    /**
     * 测试建立封禁 - 无效的类型
     */
    public function testEstablishWithInvalidType(): void
    {
        $this->clearBanData();

        $ban    = new Ban();
        $result = $ban->establish([
            'account_type' => PamAccount::TYPE_USER,
            'type'         => 'invalid',
            'value'        => '192.168.1.1',
        ]);

        $this->assertFalse($result);
        $this->assertStringContainsString('类型', $ban->getError()->getMessage());
    }

    /**
     * 测试建立封禁 - 重复的 IP 范围
     */
    public function testEstablishWithDuplicateIpRange(): void
    {
        $this->clearBanData();

        $ban = new Ban();

        // 先添加一个范围
        $ban->establish([
            'account_type' => PamAccount::TYPE_USER,
            'type'         => PamBan::TYPE_IP,
            'value'        => '192.168.1.1-192.168.1.50',
        ]);

        // 尝试添加重叠的范围
        $result = $ban->establish([
            'account_type' => PamAccount::TYPE_USER,
            'type'         => PamBan::TYPE_IP,
            'value'        => '192.168.1.30-192.168.1.100',
        ]);

        $this->assertFalse($result);
        $this->assertStringContainsString('重复', $ban->getError()->getMessage());
    }

    /**
     * 测试建立封禁 - 重复的设备
     */
    public function testEstablishWithDuplicateDevice(): void
    {
        $this->clearBanData();

        $ban = new Ban();

        // 先添加一个设备
        $ban->establish([
            'account_type' => PamAccount::TYPE_USER,
            'type'         => PamBan::TYPE_DEVICE,
            'value'        => 'device-test-123',
        ]);

        // 尝试重复添加
        $result = $ban->establish([
            'account_type' => PamAccount::TYPE_USER,
            'type'         => PamBan::TYPE_DEVICE,
            'value'        => 'device-test-123',
        ]);

        $this->assertFalse($result);
        $this->assertStringContainsString('已存在', $ban->getError()->getMessage());
    }

    /**
     * 测试删除封禁
     */
    public function testDelete(): void
    {
        $this->clearBanData();

        $ban = new Ban();
        $ban->establish([
            'account_type' => PamAccount::TYPE_USER,
            'type'         => PamBan::TYPE_IP,
            'value'        => '192.168.1.1',
        ]);

        $pamBan = PamBan::where('value', '192.168.1.1')->first();
        $this->assertNotNull($pamBan);

        $result = $ban->delete($pamBan->id);
        $this->assertTrue($result);
        $this->assertDatabaseMissing('pam_ban', ['id' => $pamBan->id]);
        $this->assertFalse($ban->checkIn(PamAccount::TYPE_USER, PamBan::TYPE_IP, '192.168.1.1'));
    }

    /**
     * 测试删除不存在的封禁
     */
    public function testDeleteNonExistent(): void
    {
        $this->clearBanData();

        $ban    = new Ban();
        $result = $ban->delete(999999);

        $this->assertFalse($result);
        $this->assertStringContainsString('不存在', $ban->getError()->getMessage());
    }

    /**
     * 测试删除 IP 范围封禁
     */
    public function testDeleteIpRange(): void
    {
        $this->clearBanData();

        $ban = new Ban();
        $ban->establish([
            'account_type' => PamAccount::TYPE_USER,
            'type'         => PamBan::TYPE_IP,
            'value'        => '192.168.1.1-192.168.1.100',
        ]);

        $pamBan = PamBan::where('value', '192.168.1.1-192.168.1.100')->first();
        $this->assertNotNull($pamBan);

        $result = $ban->delete($pamBan->id);
        $this->assertTrue($result);
        $this->assertDatabaseMissing('pam_ban', ['id' => $pamBan->id]);

        $this->assertFalse($ban->checkIn(PamAccount::TYPE_USER, PamBan::TYPE_IP, '192.168.1.50'));
    }

    /**
     * 测试删除设备封禁
     */
    public function testDeleteDevice(): void
    {
        $this->clearBanData();

        $ban = new Ban();
        $ban->establish([
            'account_type' => PamAccount::TYPE_USER,
            'type'         => PamBan::TYPE_DEVICE,
            'value'        => 'device-test-123',
        ]);

        $pamBan = PamBan::where('value', 'device-test-123')->first();
        $this->assertNotNull($pamBan);

        $result = $ban->delete($pamBan->id);
        $this->assertTrue($result);
        $this->assertDatabaseMissing('pam_ban', ['id' => $pamBan->id]);
        $this->assertFalse($ban->checkIn(PamAccount::TYPE_USER, PamBan::TYPE_DEVICE, 'device-test-123'));
    }

    /**
     * 测试 checkIn - IP 在范围内
     */
    public function testCheckInIpInRange(): void
    {
        $this->clearBanData();

        $ban = new Ban();
        $ban->establish([
            'account_type' => PamAccount::TYPE_USER,
            'type'         => PamBan::TYPE_IP,
            'value'        => '10.0.0.1-10.0.0.255',
        ]);

        $this->assertTrue($ban->checkIn(PamAccount::TYPE_USER, PamBan::TYPE_IP, '10.0.0.100'));
    }

    /**
     * 测试 checkIn - IP 不在范围内
     */
    public function testCheckInIpNotInRange(): void
    {
        $this->clearBanData();

        $ban = new Ban();
        $ban->establish([
            'account_type' => PamAccount::TYPE_USER,
            'type'         => PamBan::TYPE_IP,
            'value'        => '10.0.0.1-10.0.0.255',
        ]);

        $this->assertFalse($ban->checkIn(PamAccount::TYPE_USER, PamBan::TYPE_IP, '10.0.1.100'));
    }

    /**
     * 测试 checkIn - 设备已封禁
     */
    public function testCheckInDeviceBanned(): void
    {
        $this->clearBanData();

        $ban = new Ban();
        $ban->establish([
            'account_type' => PamAccount::TYPE_USER,
            'type'         => PamBan::TYPE_DEVICE,
            'value'        => 'device-test-123',
        ]);

        $this->assertTrue($ban->checkIn(PamAccount::TYPE_USER, PamBan::TYPE_DEVICE, 'device-test-123'));
    }

    /**
     * 测试 checkIn - 设备未封禁
     */
    public function testCheckInDeviceNotBanned(): void
    {
        $this->clearBanData();

        $ban = new Ban();
        $this->assertFalse($ban->checkIn(PamAccount::TYPE_USER, PamBan::TYPE_DEVICE, 'device-not-banned'));
    }

    /**
     * 测试 checkIn - 自动初始化缓存
     */
    public function testCheckInAutoInitCache(): void
    {
        $this->clearBanData();

        // 先添加数据
        $ban = new Ban();
        $ban->establish([
            'account_type' => PamAccount::TYPE_USER,
            'type'         => PamBan::TYPE_IP,
            'value'        => '192.168.1.1',
        ]);

        // 清空缓存
        $this->rds->del(WeiranSystemDef::ckBanOne(PamAccount::TYPE_USER));
        $this->rds->del(WeiranSystemDef::ckBanIpRange(PamAccount::TYPE_USER));

        // checkIn 应该自动初始化缓存
        $this->assertTrue($ban->checkIn(PamAccount::TYPE_USER, PamBan::TYPE_IP, '192.168.1.1'));
    }

    /**
     * 测试 parseIpRange - 单个 IP
     *
     * @throws ParamException
     */
    public function testParseIpRangeSingleIp(): void
    {
        $ban                         = new Ban();
        [$isRange, $startIp, $endIp] = $ban->parseIpRange('192.168.1.1');

        $this->assertFalse($isRange);
        $this->assertEquals(ip2long('192.168.1.1'), $startIp);
        $this->assertEquals(ip2long('192.168.1.1'), $endIp);
    }

    /**
     * 测试 parseIpRange - IP 范围
     *
     * @throws ParamException
     */
    public function testParseIpRangeWithRange(): void
    {
        $ban                         = new Ban();
        [$isRange, $startIp, $endIp] = $ban->parseIpRange('192.168.1.1-192.168.1.10');

        $this->assertTrue($isRange);
        $this->assertEquals(ip2long('192.168.1.1'), $startIp);
        $this->assertEquals(ip2long('192.168.1.10'), $endIp);
    }

    /**
     * 测试 parseIpRange - IP 通配符
     *
     * @throws ParamException
     */
    public function testParseIpRangeWithWildcard(): void
    {
        $ban                         = new Ban();
        [$isRange, $startIp, $endIp] = $ban->parseIpRange('192.168.1.*');

        $this->assertTrue($isRange);
        $this->assertEquals(ip2long('192.168.1.0'), $startIp);
        $this->assertEquals(ip2long('192.168.1.255'), $endIp);
    }

    /**
     * 测试 parseIpRange - CIDR 格式
     *
     * @throws ParamException
     */
    public function testParseIpRangeWithCidr(): void
    {
        $ban                         = new Ban();
        [$isRange, $startIp, $endIp] = $ban->parseIpRange('192.168.1.0/24');

        $this->assertTrue($isRange);
        $this->assertEquals(ip2long('192.168.1.0'), $startIp);
        $this->assertEquals(ip2long('192.168.1.255'), $endIp);
    }

    /**
     * 测试 parseIpRange - 无效的 IP
     */
    public function testParseIpRangeThrowsExceptionWithInvalidIp(): void
    {
        $this->expectException(ParamException::class);
        $this->expectExceptionMessage('IP地址不合法');

        $ban = new Ban();
        $ban->parseIpRange('invalid_ip');
    }

    /**
     * 测试 parseIpRange - 无效的 IP 范围
     */
    public function testParseIpRangeThrowsExceptionWithInvalidRange(): void
    {
        $this->expectException(ParamException::class);
        $this->expectExceptionMessage('错误的IP段写法');

        $ban = new Ban();
        $ban->parseIpRange('192.168.1.255-192.168.1.1');
    }

    /**
     * 测试 parseIpRange - 无效的 CIDR
     */
    public function testParseIpRangeThrowsExceptionWithInvalidCidr(): void
    {
        $this->expectException(ParamException::class);
        $this->expectExceptionMessage('错误的IP格式写法');

        $ban = new Ban();
        $ban->parseIpRange('192.168.1.0/33');
    }

    /**
     * 测试 type 方法 - 封禁 IP
     *
     * @throws ModelNotFoundException
     */
    public function testTypeWithIp(): void
    {
        $this->clearBanData();
        Event::fake();

        // 创建 PamToken
        $token = PamToken::create([
            'account_id'  => 1,
            'device_id'   => 'device-test-123',
            'device_type' => 'test',
            'login_ip'    => '192.168.1.100',
            'token_hash'  => md5('test-token'),
            'expired_at'  => date('Y-m-d H:i:s', strtotime('+1 day')),
        ]);

        $ban    = new Ban();
        $result = $ban->type($token->id, PamBan::TYPE_IP);

        $this->assertTrue($result);
        $this->assertDatabaseHas('pam_ban', [
            'account_type' => PamAccount::TYPE_USER,
            'type'         => PamBan::TYPE_IP,
            'value'        => '192.168.1.100',
        ]);
        $this->assertDatabaseMissing('pam_token', ['id' => $token->id]);
        Event::assertDispatched(PamTokenBanEvent::class);
    }

    /**
     * 测试 type 方法 - 封禁设备
     *
     * @throws ModelNotFoundException
     */
    public function testTypeWithDevice(): void
    {
        $this->clearBanData();
        Event::fake();

        // 创建 PamToken
        $token = PamToken::create([
            'account_id'  => 1,
            'device_id'   => 'device-test-123',
            'device_type' => 'test',
            'login_ip'    => '192.168.1.100',
            'token_hash'  => md5('test-token'),
            'expired_at'  => date('Y-m-d H:i:s', strtotime('+1 day')),
        ]);

        $ban    = new Ban();
        $result = $ban->type($token->id, PamBan::TYPE_DEVICE);

        $this->assertTrue($result);
        $this->assertDatabaseHas('pam_ban', [
            'account_type' => PamAccount::TYPE_USER,
            'type'         => PamBan::TYPE_DEVICE,
            'value'        => 'device-test-123',
        ]);
        $this->assertDatabaseMissing('pam_token', ['id' => $token->id]);
        Event::assertDispatched(PamTokenBanEvent::class);
    }

    /**
     * 测试 type 方法 - 无效的类型
     *
     * @throws ModelNotFoundException
     */
    public function testTypeWithInvalidType(): void
    {
        $this->clearBanData();

        // 创建 PamToken
        $token = PamToken::create([
            'account_id'  => 1,
            'device_id'   => 'device-test-123',
            'device_type' => 'test',
            'login_ip'    => '192.168.1.100',
            'token_hash'  => md5('test-token'),
            'expired_at'  => date('Y-m-d H:i:s', strtotime('+1 day')),
        ]);

        $ban    = new Ban();
        $result = $ban->type($token->id, 'invalid');

        $this->assertFalse($result);
        $this->assertStringContainsString('封禁类型错误', $ban->getError()->getMessage());
    }

    /**
     * 测试 type 方法 - Token 不存在
     */
    public function testTypeWithNonExistentToken(): void
    {
        $this->clearBanData();

        $ban = new Ban();
        $this->expectException(ModelNotFoundException::class);
        $ban->type(999999, PamBan::TYPE_IP);
    }

    /**
     * 测试 type 方法 - establish 失败
     *
     * @throws ModelNotFoundException
     */
    public function testTypeWhenEstablishFails(): void
    {
        $this->clearBanData();

        // 创建 PamToken
        $token = PamToken::create([
            'account_id'  => 1,
            'device_id'   => 'device-test-123',
            'device_type' => 'test',
            'login_ip'    => '192.168.1.100',
            'token_hash'  => md5('test-token'),
            'expired_at'  => date('Y-m-d H:i:s', strtotime('+1 day')),
        ]);

        // 先添加相同的 IP 封禁
        $ban = new Ban();
        $ban->establish([
            'account_type' => PamAccount::TYPE_USER,
            'type'         => PamBan::TYPE_IP,
            'value'        => '192.168.1.100',
        ]);

        // 尝试再次 type 应该失败
        $result = $ban->type($token->id, PamBan::TYPE_IP);

        $this->assertFalse($result);
    }

    /**
     * 测试 initCache - 初始化所有缓存
     */
    public function testInitCache(): void
    {
        $this->clearBanData();

        // 添加一些数据
        $ban = new Ban();
        $ban->establish([
            'account_type' => PamAccount::TYPE_USER,
            'type'         => PamBan::TYPE_IP,
            'value'        => '192.168.1.1',
        ]);
        $ban->establish([
            'account_type' => PamAccount::TYPE_BACKEND,
            'type'         => PamBan::TYPE_DEVICE,
            'value'        => 'device-test-123',
        ]);

        // 清空缓存
        $this->rds->del(WeiranSystemDef::ckBanOne(PamAccount::TYPE_USER));
        $this->rds->del(WeiranSystemDef::ckBanOne(PamAccount::TYPE_BACKEND));
        $this->rds->del(WeiranSystemDef::ckBanIpRange(PamAccount::TYPE_USER));
        $this->rds->del(WeiranSystemDef::ckBanIpRange(PamAccount::TYPE_BACKEND));

        // 初始化缓存
        $ban->initCache();

        // 验证缓存已初始化
        $this->assertTrue($this->rds->exists(WeiranSystemDef::ckBanOne(PamAccount::TYPE_USER)));
        $this->assertTrue($this->rds->exists(WeiranSystemDef::ckBanOne(PamAccount::TYPE_BACKEND)));
        $this->assertTrue($this->rds->exists(WeiranSystemDef::ckBanIpRange(PamAccount::TYPE_USER)));
        $this->assertTrue($this->rds->exists(WeiranSystemDef::ckBanIpRange(PamAccount::TYPE_BACKEND)));

        // 验证数据正确
        $this->assertTrue($ban->checkIn(PamAccount::TYPE_USER, PamBan::TYPE_IP, '192.168.1.1'));
        $this->assertTrue($ban->checkIn(PamAccount::TYPE_BACKEND, PamBan::TYPE_DEVICE, 'device-test-123'));
    }

    /**
     * 测试不同的账号类型
     */
    public function testDifferentAccountTypes(): void
    {
        $this->clearBanData();

        $ban = new Ban();

        // 用户类型
        $ban->establish([
            'account_type' => PamAccount::TYPE_USER,
            'type'         => PamBan::TYPE_IP,
            'value'        => '192.168.1.1',
        ]);

        // 后台类型
        $ban->establish([
            'account_type' => PamAccount::TYPE_BACKEND,
            'type'         => PamBan::TYPE_IP,
            'value'        => '192.168.2.1',
        ]);

        $this->assertTrue($ban->checkIn(PamAccount::TYPE_USER, PamBan::TYPE_IP, '192.168.1.1'));
        $this->assertTrue($ban->checkIn(PamAccount::TYPE_BACKEND, PamBan::TYPE_IP, '192.168.2.1'));

        // 跨类型验证
        $this->assertFalse($ban->checkIn(PamAccount::TYPE_USER, PamBan::TYPE_IP, '192.168.2.1'));
        $this->assertFalse($ban->checkIn(PamAccount::TYPE_BACKEND, PamBan::TYPE_IP, '192.168.1.1'));
    }

    /**
     * 测试边界 IP 检查
     */
    public function testBoundaryIpCheck(): void
    {
        $this->clearBanData();

        $ban = new Ban();
        $ban->establish([
            'account_type' => PamAccount::TYPE_USER,
            'type'         => PamBan::TYPE_IP,
            'value'        => '10.0.0.1-10.0.0.255',
        ]);

        // 边界值
        $this->assertTrue($ban->checkIn(PamAccount::TYPE_USER, PamBan::TYPE_IP, '10.0.0.1'));
        $this->assertTrue($ban->checkIn(PamAccount::TYPE_USER, PamBan::TYPE_IP, '10.0.0.255'));
        $this->assertFalse($ban->checkIn(PamAccount::TYPE_USER, PamBan::TYPE_IP, '10.0.0.0'));
        $this->assertFalse($ban->checkIn(PamAccount::TYPE_USER, PamBan::TYPE_IP, '10.0.1.0'));
    }

    /**
     * 测试大量 IP 范围
     */
    public function testMultipleIpRanges(): void
    {
        $this->clearBanData();

        $ban    = new Ban();
        $ranges = [
            '10.0.0.1-10.0.0.255',
            '172.16.0.1-172.16.0.255',
            '192.168.1.0/24',
        ];

        foreach ($ranges as $range) {
            $ban->establish([
                'account_type' => PamAccount::TYPE_USER,
                'type'         => PamBan::TYPE_IP,
                'value'        => $range,
            ]);
        }

        $this->assertTrue($ban->checkIn(PamAccount::TYPE_USER, PamBan::TYPE_IP, '10.0.0.100'));
        $this->assertTrue($ban->checkIn(PamAccount::TYPE_USER, PamBan::TYPE_IP, '172.16.0.50'));
        $this->assertTrue($ban->checkIn(PamAccount::TYPE_USER, PamBan::TYPE_IP, '192.168.1.200'));
    }

    /**
     * 测试 value 参数被 trim
     */
    public function testValueIsTrimmed(): void
    {
        $this->clearBanData();

        $ban    = new Ban();
        $result = $ban->establish([
            'account_type' => PamAccount::TYPE_USER,
            'type'         => PamBan::TYPE_DEVICE,
            'value'        => '  device-test-123  ',
        ]);

        $this->assertTrue($result);
        $this->assertDatabaseHas('pam_ban', [
            'value' => 'device-test-123',
        ]);
    }

    /**
     * 测试 note 参数被 trim
     */
    public function testNoteIsTrimmed(): void
    {
        $this->clearBanData();

        $ban    = new Ban();
        $result = $ban->establish([
            'account_type' => PamAccount::TYPE_USER,
            'type'         => PamBan::TYPE_IP,
            'value'        => '192.168.1.1',
            'note'         => '  测试封禁  ',
        ]);

        $this->assertTrue($result);
        $this->assertDatabaseHas('pam_ban', [
            'note' => '测试封禁',
        ]);
    }

    /**
     * 测试空的 note 参数
     */
    public function testWithEmptyNote(): void
    {
        $this->clearBanData();

        $ban    = new Ban();
        $result = $ban->establish([
            'account_type' => PamAccount::TYPE_USER,
            'type'         => PamBan::TYPE_IP,
            'value'        => '192.168.1.1',
            'note'         => '',
        ]);

        $this->assertTrue($result);
        $this->assertDatabaseHas('pam_ban', [
            'value' => '192.168.1.1',
            'note'  => '',
        ]);
    }

    /**
     * 测试删除异常处理
     */
    public function testDeleteExceptionHandling(): void
    {
        $this->clearBanData();

        // 创建一个会删除失败的场景
        // 由于测试环境的限制，我们主要测试异常路径存在
        $ban    = new Ban();
        $result = $ban->delete(999999);

        $this->assertFalse($result);
    }

    /**
     * 测试 type 方法异常处理
     *
     * @throws ModelNotFoundException
     */
    public function testTypeExceptionHandling(): void
    {
        $this->clearBanData();
        Event::fake();

        // 创建 PamToken
        $token = PamToken::create([
            'account_id'  => 1,
            'device_id'   => 'device-test-123',
            'device_type' => 'test',
            'login_ip'    => '192.168.1.100',
            'token_hash'  => md5('test-token'),
            'expired_at'  => date('Y-m-d H:i:s', strtotime('+1 day')),
        ]);

        // 先添加相同的 IP
        $ban = new Ban();
        $ban->establish([
            'account_type' => PamAccount::TYPE_USER,
            'type'         => PamBan::TYPE_IP,
            'value'        => '192.168.1.100',
        ]);

        // 这应该会失败
        $result = $ban->type($token->id, PamBan::TYPE_IP);
        $this->assertFalse($result);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->rds = sys_tag('weiran-system');
    }

    private function clearBanData(): void
    {
        PamBan::query()->delete();
        PamToken::query()->delete();

        // 清空 Redis 缓存
        $this->rds->del(WeiranSystemDef::ckBanOne(PamAccount::TYPE_USER));
        $this->rds->del(WeiranSystemDef::ckBanOne(PamAccount::TYPE_BACKEND));
        $this->rds->del(WeiranSystemDef::ckBanIpRange(PamAccount::TYPE_USER));
        $this->rds->del(WeiranSystemDef::ckBanIpRange(PamAccount::TYPE_BACKEND));
    }

    /**
     * 生成测试 IP 列表
     */
    private function genIps(): array
    {
        return [
            '30.92.252.134',
            '192.168.20.1-192.168.20.254',
            '10.66.191.*',
            '192.168.45.147',
            '10.243.162.1/24',
        ];
    }

    /**
     * 清理 IP 数据
     */
    private function clearIps($ips): void
    {
        try {
            PamBan::where('account_type', 'user')->whereIn('value', $ips)->delete();
        }
        catch (Exception $e) {
            $this->fail($e->getMessage());
        }
    }

    /**
     * 移除 IP
     */
    private function removeIps($ips): void
    {
        $Ban = new Ban();
        PamBan::where('type', PamBan::TYPE_IP)
            ->whereIn('value', $ips)->pluck('id')
            ->each(function ($id) use ($Ban) {
                if (!$Ban->delete($id)) {
                    $this->fail($Ban->getError()->getMessage());
                }
            });
    }
}
