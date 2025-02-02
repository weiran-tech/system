<?php

namespace Weiran\System\Tests\Models;

use Exception;
use Weiran\Framework\Application\TestCase;
use Weiran\Framework\Exceptions\ApplicationException;
use Weiran\System\Action\Role;
use Weiran\System\Models\PamAccount;
use Weiran\System\Models\PamPermission;
use Weiran\System\Models\PamPermissionRole;

class PamPermissionTest extends TestCase
{

    /**
     * @throws ApplicationException
     * @throws Exception
     */
    public function testSync(): void
    {
        // 创建权限
        $permission = PamPermission::firstOrCreate([
            'name' => 'testing:a.b.c',
        ], [
            'title' => 'testing-sync-' . $this->faker()->bothify('???###'),
        ]);

        $Role = new Role();
        if ($Role->establish([
            'title' => $this->faker()->lexify('testing-back-sync-????'),
            'type'  => PamAccount::TYPE_BACKEND,
        ])) {
            $this->assertTrue(true);
        }
        $role = $Role->getRole();

        PamPermissionRole::create([
            'role_id'       => $role->id,
            'permission_id' => $permission->id,
        ]);

        $permission->delete();
        $role->delete();
        $this->assertCount(0, PamPermissionRole::where('role_id', $role->id)->get());
    }
}