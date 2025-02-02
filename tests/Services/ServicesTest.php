<?php

namespace Weiran\System\Tests\Services;

use Weiran\Core\Classes\PyCoreDef;
use Weiran\Framework\Application\TestCase;

class ServicesTest extends TestCase
{

    public function setUp(): void
    {
        parent::setUp();

        sys_tag('py-core')->del(PyCoreDef::ckModule('hook'));
        sys_tag('py-core')->del(PyCoreDef::ckModule('module'));
    }

    public function testUploadType()
    {
        $uploadTypes = sys_hook('poppy.system.upload_type');
        self::assertArrayHasKey('default', $uploadTypes);
    }
}