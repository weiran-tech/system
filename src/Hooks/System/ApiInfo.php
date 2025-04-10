<?php

declare(strict_types = 1);

namespace Weiran\System\Hooks\System;

use Weiran\Core\Services\Contracts\ServiceArray;

class ApiInfo implements ServiceArray
{

    public function key(): string
    {
        return 'weiran-system';
    }

    public function data(): array
    {
        return [
            'title' => sys_setting('weiran-system::site.name'),
            'logo'  => sys_setting('weiran-system::site.logo'),
        ];
    }
}