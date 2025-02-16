<?php

declare(strict_types = 1);

namespace Weiran\System\Tests\Action;

use Artisan;
use Exception;
use Weiran\Framework\Application\TestCase;
use Weiran\System\Action\Ban;
use Weiran\System\Models\PamBan;

/**
 * 禁用测试
 */
class BanTest extends TestCase
{

    /**
     */
    public function testIpv4Command(): void
    {
        $ips = $this->genIps();
        $this->clearIps($ips);
        foreach ($ips as $ip) {
            $code = Artisan::call('weiran:system:ban', [
                'type'  => 'backend',
                'value' => $ip,
            ]);
            $this->assertEquals(0, $code, "ip value is : {$ip}");
        }
        $this->removeIps($ips);


        // 错误 IP
        $code = Artisan::call('weiran:system:ban', [
            'type'  => 'backend',
            'value' => 'error-ip',
        ]);
        $this->assertEquals(1, $code);


        // 错误的用户类型
        $code = Artisan::call('weiran:system:ban', [
            'type'  => 'error-type',
            'value' => '127.0.0.1',
        ]);
        $this->assertEquals(1, $code);
    }


    /**
     * Ip 测试
     */
    public function testIpv4Matched(): void
    {
        $ips = [
            "136.60.196.79",
            "10.205.182.1-10.205.182.254",
            "172.31.204.*",
            "172.20.76.100",
            "192.168.81.1/24",
        ];

        $this->clearIps($ips);
        $Ban = new Ban();
        foreach ($ips as $ip) {
            // range
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
     * 添加随机IP 范围
     * @return void
     */
    public function testCreate(): void
    {

        $ips = $this->genIps();
        $this->clearIps($ips);
        $Ban = new Ban();
        foreach ($ips as $ip) {
            // range
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
     * 获取 IP
     */
    private function genIps(): array
    {
        return [
            "30.92.252.134",
            "192.168.20.1-192.168.20.254",
            "10.66.191.*",
            "192.168.45.147",
            "10.243.162.1/24",
        ];
    }

    private function clearIps($ips): void
    {
        try {
            PamBan::where('account_type', 'user')->whereIn('value', $ips)->delete();
        } catch (Exception $e) {
            $this->fail($e->getMessage());
        }
    }

    /**
     * 移除 IP
     * @param $ips
     * @return void
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