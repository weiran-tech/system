<?php

namespace Weiran\System\Tests\Ability;

use Weiran\Core\Classes\Traits\CoreTrait;
use Weiran\Core\Rbac\Permission\Permission;
use Weiran\Framework\Application\TestCase;
use Weiran\System\Models\PamPermission;
use Weiran\System\Models\PamRole;
use Weiran\System\Tests\Testing\TestingPam;

class PermissionTest extends TestCase
{
    use CoreTrait;

    /**
     * 检测权限不为空
     */
    public function testPermissions(): void
    {
        $permissions = $this->corePermission()->permissions();
        $items       = $permissions->map(function (Permission $permission) {
            return [
                $permission->module(),
                $permission->type(),
                $permission->key(),
                $permission->groupTitle(),
            ];
        });

        // $this->table(['module', 'type', 'key', 'title'], $items);

        $this->assertNotEmpty($items);
    }

    /**
     * 检测是否存在指定权限
     */
    public function testHasPermission(): void
    {
        $pam = TestingPam::randUser();
        /** @var PamRole $role */
        $role = PamRole::where('name', 'user')->first();


        $key = 'backend:weiran-system.global.manage';

        /** @var Permission $permission */
        $permission = $this->corePermission()->permissions()->offsetGet($key);


        if ($permission) {
            $dbPerm = PamPermission::where('name', $key)->first();
            if ($role->hasPermission($key)) {
                $role->detachPermission($dbPerm);
            }
            $role->attachPermission($dbPerm);
            $role->save();
            if ($pam->capable($permission->key())) {
                $this->assertTrue(true);
            }
            else {
                $this->fail("没有 '{$permission->description()} '权限, 无权操作");
            }
        }
        else {
            $this->fail('Permission Not Exists!');
        }
    }
}
