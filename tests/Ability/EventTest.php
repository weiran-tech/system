<?php

declare(strict_types = 1);

namespace Weiran\System\Tests\Ability;

use Carbon\Carbon;
use Weiran\Framework\Application\TestCase;
use Weiran\System\Events\PamDisableEvent;
use Weiran\System\Tests\Testing\TestingPam;

class EventTest extends TestCase
{
    public function testPamDisable(): void
    {
        $pam = TestingPam::randUser();
        event(new PamDisableEvent($pam, $pam, 'Testing Event dispatched @ ' . Carbon::now()));
        $this->assertTrue(true);
    }
}
