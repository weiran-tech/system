<?php

declare(strict_types = 1);

namespace Weiran\System\Tests\Classes\File;

use PHPUnit\Framework\TestCase;
use Weiran\System\Classes\File\FileManager;

class FileManagerTest extends TestCase
{

    public function testResizedSize(): void
    {

        // 短边是宽度
        $width     = 2000;
        $height    = 3000;
        $minResize = 980;
        $result    = FileManager::resizedSize($width, $height, $minResize, null);
        $this->assertSame([980, null, true], [$result['width'], $result['height'], $result['resize']]);


        // 短边是高度
        $width     = 2100;
        $height    = 1800;
        $minResize = 1080;
        $result    = FileManager::resizedSize($width, $height, $minResize, null);
        $this->assertSame([null, 1080, true], [$result['width'], $result['height'], $result['resize']]);


        // 正方形, 先以高度作为限定
        $width     = 2200;
        $height    = 2200;
        $minResize = 980;
        $result    = FileManager::resizedSize($width, $height, $minResize, null);
        $this->assertSame([null, 980, true], [$result['width'], $result['height'], $result['resize']]);

        // 两个限制比值, 以最小比例做限定
        $width     = 1500;
        $height    = 900;
        $maxResize = 1450;
        $minResize = 800;
        $result    = FileManager::resizedSize($width, $height, $minResize, $maxResize);
        $this->assertSame([1333, 800, true], [$result['width'], $result['height'], $result['resize']]);

        // 不进行压缩
        $width     = 1520;
        $height    = 910;
        $minResize = 999;
        $maxResize = 1600;
        $result    = FileManager::resizedSize($width, $height, $minResize, $maxResize);
        $this->assertSame([1520, 910, false], [$result['width'], $result['height'], $result['resize']]);

        // 两个限制比值, 以最小比例做限定
        $width     = 1080;
        $height    = 37183;
        $minResize = 1920;
        $maxResize = 30000;
        $result    = FileManager::resizedSize($width, $height, $minResize, $maxResize);
        $this->assertSame([null, 30000, true], [$result['width'], $result['height'], $result['resize']]);
    }
}
