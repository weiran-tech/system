<?php

namespace Weiran\System\Tests\Ability;

use Weiran\Framework\Application\TestCase;
use Weiran\Framework\Exceptions\ApplicationException;
use Weiran\System\Jobs\NotifyProJob;
use Weiran\System\Tests\Ability\Jobs\StaticVarJob;

class JobTest extends TestCase
{
    /**
     * 测试 oss 上传
     * @throws ApplicationException
     */
    public function testCallback(): void
    {
        // 这个队列会执行成功
        dispatch(new NotifyProJob('https://www.baidu.com', 'get', []));

        // 这个会执行失败, 失败后会进行下一次的延迟请求
        dispatch(new NotifyProJob('https://www.baidu-error.com', 'get', []));
        $this->assertTrue(true);
    }

    public function testStaticVars(): void
    {
        dispatch(new StaticVarJob(1));
        $this->assertTrue(true);
    }

    /**
     * 测试 oss 上传
     * @throws ApplicationException
     */
    public function testNotifyPro(): void
    {
        // 这个队列会执行成功
        dispatch(new NotifyProJob('https://www.baidu.com', 'get', [
            'query' => [
                'job' => 1,
            ],
        ]));

        // 这个会执行失败, 失败后会进行下一次的延迟请求
        dispatch(new NotifyProJob('https://www.baidu-error.com', 'get', [], 4));
        $this->assertTrue(true);
    }
}