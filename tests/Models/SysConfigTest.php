<?php

namespace Weiran\System\Tests\Models;

use Weiran\Framework\Application\TestCase;
use Weiran\Framework\Exceptions\ApplicationException;
use Weiran\System\Models\PamAccount;
use Weiran\System\Models\SysConfig;

class SysConfigTest extends TestCase
{
    /**
     * @throws ApplicationException
     */
    public function testTableExist(): void
    {
        $exist = SysConfig::tableExists((new PamAccount())->getTable());
        $this->assertTrue($exist);

        $tbExists = SysConfig::tableExists($this->faker()->lexify());
        $this->assertFalse($tbExists);
    }

    public function tearDown(): void
    {
        app('weiran.system.setting')->removeNG('weiran-system::db');
    }
}