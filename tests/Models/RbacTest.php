<?php

declare(strict_types = 1);

namespace Weiran\System\Tests\Models;

use Weiran\Core\Classes\PyCoreDef;
use Weiran\Framework\Application\TestCase;
use Weiran\Framework\Exceptions\ApplicationException;
use Weiran\System\Action\Pam;
use Weiran\System\Classes\Traits\DbTrait;
use Weiran\System\Models\PamAccount;
use Weiran\System\Models\PamRole;
use Weiran\System\Tests\Testing\TestingPam;
use Weiran\System\Tests\Testing\TestingRole;
use Throwable;

class RbacTest extends TestCase
{
    use DbTrait;

    /**
     * @return void
     * @throws ApplicationException
     * @throws Throwable
     */
    public function testCachedRoles(): void
    {
        // 创建后台用户
        $pam           = new Pam();
        $fakerUsername = py_faker()->lexify('????????');
        if (!$pam->register('be_' . $fakerUsername, $fakerUsername, PamRole::BE_ROOT)) {
            $this->fail($pam->getError()->getMessage());
        }

        $pam  = TestingPam::randBackend();
        $key  = PyCoreDef::rbacCkUserRoles($pam->id);
        $role = TestingRole::randBackend();
        // 获取用户的缓存角色, 缓存存在值
        $pam->cachedRoles();
        $this->assertTrue(sys_tag('weiran-core-rbac')->exists($key));
        $pam->attachRole($role);
        $pam->detachRole($role);
        $pam->attachRole($role->id);
        $pam->detachRole($role->id);
        $pam->attachRole([$role->id]);
        $pam->detachRole([$role->id]);
        $pam->attachRole([$role->id]);
        $pam->detachRole([$role->id]);

        // 缓存不存在
        $this->assertNotTrue(sys_tag('weiran-core-rbac')->exists($key));

        // 缓存成功
        $pam->cachedRoles();

        // 缓存存在
        $this->assertTrue(sys_tag('weiran-core-rbac')->exists($key));
    }


    public function testPermissions()
    {
        $pam         = TestingPam::randBackend();
        $permissions = PamAccount::permissions($pam);
        $this->assertNotNull($permissions, 'User has no permission');
        $names = $permissions->pluck('name');
        $this->assertNotNull($names, 'User has no permission');
    }
}