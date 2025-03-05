<?php

namespace Weiran\System\Tests\Action;

use Weiran\Framework\Application\TestCase;
use Weiran\System\Classes\Contracts\FileContract;
use Weiran\System\Classes\File\DefaultFileProvider;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Throwable;

/**
 * 上传测试[本地上传测试]
 */
class UploadTest extends TestCase
{

    /**
     * 进行上传
     */
    public function testUpload(): void
    {
        try {
            $file   = weiran_path('weiran.system', 'tests/files/demo.jpg');
            $image  = new UploadedFile($file, 'test.jpg', null, null, true);
            $Upload = new DefaultFileProvider();

            $Upload->setExtension(['jpg']);

            if (!$Upload->saveFile($image)) {
                $this->fail($Upload->getError());
            }

            // 检测文件存在
            $url = $Upload->getUrl();
            if (file_get_contents($url)) {
                $this->assertTrue(true);
                $path = base_path('public/' . $Upload->getDestination());
                $this->outputVariables($path);
                $result = app('files')->delete(base_path('public/' . $Upload->getDestination()));
                $this->assertTrue($result);
            }
            else {
                $this->fail("Url {$url} 不可访问!");
            }
        } catch (Throwable $e) {
            $this->fail($e->getMessage());
        }
    }

    /**
     * 进行上传
     */
    public function testDest(): void
    {
        try {
            $file   = weiran_path('weiran.system', 'tests/files/demo.jpg');
            $image  = new UploadedFile($file, 'test.jpg', null, null, true);
            $Upload = new DefaultFileProvider();

            $Upload->setExtension(['jpg']);
            $path = 'dev/testing/upload-dest.jpg';
            $Upload->setIsForceSetDestination(true);
            $Upload->setDestination($path);
            if (!$Upload->saveFile($image)) {
                $this->fail($Upload->getError());
            }

            // 检测文件存在
            $url = $Upload->getReturnUrl() . $path;
            if (file_get_contents($url)) {
                $this->assertTrue(true);
                $path = base_path('public/' . $Upload->getDestination());
                $this->outputVariables($path);
                $result = app('files')->delete(base_path('public/' . $Upload->getDestination()));
                $this->assertTrue($result);
            }
            else {
                $this->fail("Url {$url} 不可访问!");
            }
        } catch (Throwable $e) {
            $this->fail($e->getMessage());
        }
    }

    /**
     * heic转jpg上传
     * @return void
     */
    public function testHeic2Jpg(): void
    {
        try {
            $file       = weiran_path('weiran.system', 'tests/files/single.heic');
            $image      = new UploadedFile($file, 'single.heic', null, null, true);

            /** @var DefaultFileProvider $Image */
            $Image = app(FileContract::class);
            $Image->setFolder('default');
            $Image->setExtension(['jpg', 'png', 'gif', 'jpeg', 'webp', 'bmp', 'heic', 'mp4', 'rm', 'rmvb', 'wmv']);
            if ($Image->saveFile($image)) {
                $url = $Image->getUrl();
                if (file_get_contents($url)) {
                    $this->assertTrue(true);
                    $this->outputVariables($url);
                }
                else {
                    $this->fail("Url {$url} 不可访问!");
                }
            }
            else {
                $this->fail($Image->getError());
            }
        } catch (Throwable $e) {
            $this->fail($e->getMessage());
        }
    }
}