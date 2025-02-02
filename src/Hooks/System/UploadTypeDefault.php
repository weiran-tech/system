<?php

declare(strict_types = 1);

namespace Weiran\System\Hooks\System;

use Weiran\Core\Services\Contracts\ServiceArray;
use Weiran\System\Classes\File\DefaultFileProvider;

class UploadTypeDefault implements ServiceArray
{

    public function key(): string
    {
        return 'default';
    }

    public function data(): array
    {
        return [
            'title'    => '默认(uploads 目录下)',
            'provider' => DefaultFileProvider::class,
        ];
    }
}