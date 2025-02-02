<?php

declare(strict_types = 1);

namespace Weiran\System\Tests\Setting;

use Exception;
use Weiran\Framework\Application\TestCase;
use Weiran\Framework\Exceptions\ApplicationException;
use Weiran\System\Exceptions\SettingKeyNotMatchException;
use Weiran\System\Exceptions\SettingValueOutOfRangeException;
use Weiran\System\Setting\Repository\SettingRepository;

class SettingTest extends TestCase
{

    /**
     * @throws SettingKeyNotMatchException
     * @throws SettingValueOutOfRangeException|ApplicationException
     */
    public function testItem(): void
    {
        $key     = $this->randKey();
        $setting = new SettingRepository();
        $this->assertTrue($setting->set($key, 'value'));
        $item = $setting->get($key);
        $this->assertEquals('value', $item, 'Value Fetch Error');
        $this->assertTrue($setting->delete($key));
    }

    /**
     * @throws ApplicationException
     */
    public function testGet(): void
    {
        $item = sys_setting($this->randKey('set'));
        $this->assertNull($item);
        $item = sys_setting($this->randKey('set'), '');
        $this->assertEmpty($item);
        $item = sys_setting($this->randKey('set'), 'testing');
        $this->assertEquals('testing', $item);
    }

    /**
     * @throws SettingValueOutOfRangeException
     * @throws SettingKeyNotMatchException
     * @throws ApplicationException
     */
    public function testGetGn(): void
    {
        app('poppy.system.setting')->removeNG('testing::set');

        // A : Str
        $keyA = $this->randKey('set');
        $valA = $this->faker()->lexify();
        app('poppy.system.setting')->set($keyA, $valA);
        $valGetA = sys_setting($keyA);
        $this->assertEquals($valA, $valGetA);

        // B : Array
        $keyB = $this->randKey('set');
        $valB = $this->faker()->words();
        app('poppy.system.setting')->set($keyB, $valB);
        $valGetB = sys_setting($keyB);
        $this->assertEquals($valB, $valGetB);

        $gn = app('poppy.system.setting')->getNG('testing::set');
        $this->assertCount(2, $gn);
    }

    /**
     * @throws SettingKeyNotMatchException
     * @throws ApplicationException
     */
    public function testOutOfRange(): void
    {
        $this->expectException(SettingValueOutOfRangeException::class);
        app('poppy.system.setting')->set($this->randKey(), str_pad('3', 65536));
    }

    /**
     * @throws SettingValueOutOfRangeException
     */
    public function testKeyNotMatch(): void
    {
        $this->expectException(SettingKeyNotMatchException::class);
        app('poppy.system.setting')->set('testing::set.name.name', 'some value');
    }

    /**
     * @throws Exception
     */
    public function tearDown(): void
    {
        app('poppy.system.setting')->removeNG('testing::set');
    }

    /**
     * @throws ApplicationException
     */
    private function randKey($group = ''): string
    {
        $faker = $this->faker();
        return 'testing::' . ($group ?: $faker->regexify('[a-z]{3,5}')) . '.' . $faker->regexify('/[a-z]{5,8}/');
    }
}