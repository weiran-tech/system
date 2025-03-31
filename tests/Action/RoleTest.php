<?php

declare(strict_types = 1);

namespace Weiran\System\Tests\Action;

use Exception;
use Weiran\Core\Classes\WeiranCoreDef;
use Weiran\Core\Classes\Traits\CoreTrait;
use Weiran\Framework\Application\TestCase;
use Weiran\Framework\Exceptions\ApplicationException;
use Weiran\System\Action\Role;
use Weiran\System\Models\PamAccount;
use Weiran\System\Models\PamPermission;
use Weiran\System\Tests\Testing\TestingPam;

class RoleTest extends TestCase
{

    use CoreTrait;

    /**
     * 角色添加和权限处理
     * @throws ApplicationException
     * @throws Exception
     */
    public function testEstablish(): void
    {
        // 一个虚拟手机号
        $Role = new Role();
        if ($Role->establish([
            'title' => $this->faker()->lexify('testing-backend-????'),
            'type'  => PamAccount::TYPE_BACKEND,
        ])) {
            $this->assertTrue(true);
        }

        $permissions = PamPermission::where('type', PamAccount::TYPE_BACKEND)->get();
        if (!$permissions->count()) {
            $this->fail('当前权限未初始化');
        }

        $first = $permissions->shuffle()->first();

        $role = $Role->getRole();
        $role->syncPermission($permissions);

        $this->assertEquals($permissions->count(), $role->cachedPermissions()->count());

        $role->detachPermission($first);

        $this->assertEquals($permissions->count() - 1, $role->cachedPermissions()->count());

        $role->attachPermission($first);

        $this->assertNotTrue(sys_tag('weiran-core-rbac')->exists(WeiranCoreDef::rbacCkRolePermissions($role->id)), '存在角色权限缓存标签');

        $this->assertEquals($permissions->count(), $role->cachedPermissions()->count());

        $role->syncPermission([]);

        $this->assertEquals(0, $role->cachedPermissions()->count());

        $id = $role->id;

        $pam = TestingPam::backend();

        $Role->setPam($pam);

        if ($Role->delete($id)) {
            $this->assertNotTrue(sys_tag('weiran-core-rbac')->exists(WeiranCoreDef::rbacCkRolePermissions($id)), '存在角色权限缓存标签');
        }
        else {
            $this->fail('此角色未删除 : ' . $Role->getError());
        }
    }
}