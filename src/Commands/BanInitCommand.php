<?php

declare(strict_types = 1);

namespace Weiran\System\Commands;

use Illuminate\Console\Command;
use Weiran\System\Action\Ban;

class BanInitCommand extends Command
{
    protected $signature = 'system:ban-init';

    protected $description = 'Ban Init';

    public function handle(): void
    {
        (new Ban())->initCache();

        $this->info('Init Ban Users Success');
    }
}