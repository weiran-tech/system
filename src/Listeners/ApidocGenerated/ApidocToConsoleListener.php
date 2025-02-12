<?php

declare(strict_types = 1);

namespace Weiran\System\Listeners\ApidocGenerated;

use Weiran\Core\Events\ApidocGeneratedEvent;
use Weiran\System\Action\Console;

/**
 * Apidoc 上传到 Console
 */
class ApidocToConsoleListener
{
    /**
     * Handle the event.
     * @param ApidocGeneratedEvent $event 用户账号
     * @return void
     */
    public function handle(ApidocGeneratedEvent $event): void
    {
        $Sso = new Console();
        if ($event->type === 'web' && !$Sso->apidocCapture($event->type)) {
            sys_info('weiran-system.apidoc', $Sso->getError()->getMessage());
        }
    }
}
