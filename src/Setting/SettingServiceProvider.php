<?php

declare(strict_types = 1);

namespace Weiran\System\Setting;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Weiran\Core\Classes\Contracts\SettingContract;
use Weiran\System\Setting\Repository\SettingRepository;

class SettingServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * @return array
     */
    public function provides(): array
    {
        return ['poppy.system.setting', SettingContract::class];
    }

    /**
     * Register for service provider.
     */
    public function register(): void
    {
        $this->app->singleton('weiran.system.setting', function () {
            return new SettingRepository();
        });
        $this->app->bind(SettingContract::class, SettingRepository::class);
    }
}
