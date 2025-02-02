<?php

namespace Weiran\System\Tests\Testing;

/**
 * 随机获取数据
 */
class TestingUtil
{
    /**
     * 获取随机用户名
     * @param $array
     * @return mixed
     */
    public static function randomKey($array)
    {
        if (!$array) {
            return '';
        }
        $keys = array_keys($array);

        return collect($keys)->shuffle()->first();
    }
}
