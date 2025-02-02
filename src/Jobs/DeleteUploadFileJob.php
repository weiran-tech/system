<?php

declare(strict_types = 1);

namespace Weiran\System\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Weiran\Framework\Application\Job;
use Weiran\Framework\Exceptions\ApplicationException;
use Weiran\System\Classes\Contracts\FileContract;

/**
 * 删除已经上传的文件
 */
class DeleteUploadFileJob extends Job implements ShouldQueue
{
    use Queueable;

    /**
     * @var string 需要删除的Url地址
     */
    private string $url;

    /**
     * 删除Url
     * @param string $url 请求的URL 地址
     */
    public function __construct(string $url)
    {
        $this->url = $url;
    }

    /**
     * 执行
     * @throws ApplicationException
     */
    public function handle()
    {
        $Upload = app(FileContract::class);
        $dest   = parse_url($this->url)['path'] ?? '';
        if (!$dest) {
            throw new ApplicationException('文件 ' . $dest . ' @ ' . $this->url . ' 不存在, 不得删除');
        }
        $Upload->setIsForceSetDestination(true);
        $Upload->setDestination(trim($dest, '/'));
        $Upload->delete();
    }
}