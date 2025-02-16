<?php

namespace Weiran\System\Tests\Support;

use Weiran\Core\Classes\PyCoreDef;
use Weiran\Framework\Application\TestCase;
use Weiran\System\Models\PamAccount;

class FunctionsTest extends TestCase
{

    public function setUp(): void
    {
        parent::setUp();

        sys_tag('weiran-core')->del(PyCoreDef::ckModule('hook'));
        sys_tag('weiran-core')->del(PyCoreDef::ckModule('module'));
    }

    public function testWeiranFriendly()
    {
        config('app.locale', 'en');
        $name = weiran_friendly(PamAccount::class);
        $this->assertEquals(trans('weiran-system::util.classes.models.pam_account'), $name);

        config('app.locale', 'zh');
        $name = weiran_friendly(PamAccount::class);
        $this->assertEquals(trans('weiran-system::util.classes.models.pam_account'), $name);
    }

    public function testSysGet()
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
}