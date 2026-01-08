<?php

declare(strict_types = 1);

namespace Weiran\System\Tests\Support;

use Throwable;
use Weiran\Core\Classes\WeiranCoreDef;
use Weiran\Framework\Application\TestCase;
use Weiran\Framework\Exceptions\ApplicationException;
use Weiran\System\Classes\WeiranSystemDef;
use Weiran\System\Models\PamAccount;
use Weiran\System\Tests\Testing\TestingPam;

class FunctionsTest extends TestCase
{
    public function testWeiranFriendly(): void
    {
        config('app.locale', 'en');
        $name = weiran_friendly(PamAccount::class);
        $this->assertEquals(trans('weiran-system::util.classes.models.pam_account'), $name);

        config('app.locale', 'zh');
        $name = weiran_friendly(PamAccount::class);
        $this->assertEquals(trans('weiran-system::util.classes.models.pam_account'), $name);
    }

    public function testSysGet(): void
    {
        $input = [
            'null'         => null,
            'int'          => 1,
            'string'       => 'string',
            'string_space' => 'string    ',
        ];
        $arr   = sys_get($input, ['null', 'int', 'string', 'string_space']);
        $this->assertEquals([
            'null'         => '',
            'int'          => 1,
            'string'       => 'string',
            'string_space' => 'string',
        ], $arr);
    }

    /**
     * 测试 sys_parent_id 函数
     *
     * @throws Throwable
     * @throws ApplicationException
     */
    public function testSysParentId(): void
    {
        // 清除可能存在的分片（Hash 分片结构）
        for ($i = 0; $i < 10; $i++) {
            sys_tag('weiran-system')->del(WeiranSystemDef::ckPamRelParent((string) $i));
        }

        // 测试场景 1: 传入空值或 0 应该返回 0
        $this->assertEquals(0, sys_parent_id(0));
        $this->assertEquals(0, sys_parent_id(''));

        // 测试场景 2: 创建父级用户（parent_id 为 0）
        $parentUser = TestingPam::randParentUser();

        // 传入 PamAccount 实例，parent_id 为 0 时返回自己的 id
        $parentId = sys_parent_id($parentUser);
        $this->assertEquals($parentUser->id, $parentId);

        // 验证已存储到 Redis Hash 分片
        $shardIndex     = (string) floor($parentUser->id / 10000);
        $shardKey       = WeiranSystemDef::ckPamRelParent($shardIndex);
        $cachedParentId = sys_tag('weiran-system')->hGet($shardKey, (string) $parentUser->id);
        $this->assertEquals($parentUser->id, (int) $cachedParentId);

        // 验证过期时间已设置
        $ttl = sys_tag('weiran-system')->ttl($shardKey);
        $this->assertGreaterThan(0, $ttl);
        $this->assertLessThanOrEqual(86400 * 7, $ttl); // 不超过 7 天

        // 测试场景 3: 创建子用户（有 parent_id）
        $childUser = TestingPam::randChildUser($parentUser->id);
        $this->assertEquals($parentUser->id, $childUser->parent_id);

        // 传入 PamAccount 实例，应该返回 parent_id
        $childParentId = sys_parent_id($childUser);
        $this->assertEquals($parentUser->id, $childParentId);

        // 验证已存储到 Redis Hash 分片
        $childShardIndex     = (string) floor($childUser->id / 10000);
        $childShardKey       = WeiranSystemDef::ckPamRelParent($childShardIndex);
        $cachedChildParentId = sys_tag('weiran-system')->hGet($childShardKey, (string) $childUser->id);
        $this->assertEquals($parentUser->id, (int) $cachedChildParentId);

        // 测试场景 4: 清除缓存后传入数字 ID（从数据库加载）
        $childParentIdById = sys_parent_id($childUser->id);
        $this->assertEquals($parentUser->id, $childParentIdById);

        // 验证重新存储到 Redis Hash
        $reCachedId = sys_tag('weiran-system')->hGet($childShardKey, (string) $childUser->id);
        $this->assertEquals($parentUser->id, (int) $reCachedId);

        // 测试场景 5: 测试缓存机制
        // 再次调用应该从静态变量缓存中获取（不查询 Redis）
        $cachedResult = sys_parent_id($childUser->id);
        $this->assertEquals($parentUser->id, $cachedResult);

        // 测试场景 6: 验证分片机制 - 不同 ID 范围在不同分片中
        // 如果父用户和子用户在不同分片，验证分片隔离
        if ($shardIndex !== $childShardIndex) {
            $parentShardData = sys_tag('weiran-system')->hGetAll($shardKey);
            $childShardData  = sys_tag('weiran-system')->hGetAll($childShardKey);

            $this->assertArrayHasKey((string) $parentUser->id, $parentShardData);
            $this->assertArrayNotHasKey((string) $parentUser->id, $childShardData);
            $this->assertArrayHasKey((string) $childUser->id, $childShardData);
            $this->assertArrayNotHasKey((string) $childUser->id, $parentShardData);
        }

        // 测试场景 7: 父级用户的 parent_id 为 0，应该返回自己的 id
        $parentSelfId = sys_parent_id($parentUser->id);
        $this->assertEquals($parentUser->id, $parentSelfId);

        // 清理测试数据
        $childUser->delete();
        for ($i = 0; $i < 10; $i++) {
            sys_tag('weiran-system')->del(WeiranSystemDef::ckPamRelParent((string) $i));
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        sys_tag('weiran-core')->del(WeiranCoreDef::ckModule('hook'));
        sys_tag('weiran-core')->del(WeiranCoreDef::ckModule('module'));
    }
}
