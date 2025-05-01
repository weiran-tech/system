<?php

declare(strict_types = 1);

namespace Weiran\System;

use Illuminate\Auth\Events\Login as AuthLoginEvent;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Events\QueryExecuted;
use JsonException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Weiran\Core\Classes\Contracts\SettingContract;
use Weiran\Core\Events\PermissionInitEvent;
use Weiran\Framework\Classes\Traits\WeiranTrait;
use Weiran\Framework\Events\WeiranOptimized;
use Weiran\Framework\Events\WeiranSchedule;
use Weiran\Framework\Exceptions\ModuleNotFoundException;
use Weiran\Framework\Support\WeiranServiceProvider;
use Weiran\System\Classes\Api\Sign\DefaultApiSignProvider;
use Weiran\System\Classes\Auth\Password\DefaultPasswordProvider;
use Weiran\System\Classes\Auth\Provider\BackendProvider;
use Weiran\System\Classes\Auth\Provider\PamProvider;
use Weiran\System\Classes\Auth\Provider\WebProvider;
use Weiran\System\Classes\Contracts\ApiSignContract;
use Weiran\System\Classes\Contracts\FileContract;
use Weiran\System\Classes\Contracts\PasswordContract;
use Weiran\System\Classes\File\DefaultFileProvider;
use Weiran\System\Events\LoginTokenPassedEvent;
use Weiran\System\Events\PamLogoutEvent;
use Weiran\System\Events\PamPasswordModifiedEvent;
use Weiran\System\Events\TokenRenewEvent;
use Weiran\System\Exceptions\SettingKeyNotMatchException;
use Weiran\System\Exceptions\SettingValueOutOfRangeException;
use Weiran\System\Models\PamAccount;
use Weiran\System\Models\PamRole;
use Weiran\System\Models\Policies\PamAccountPolicy;
use Weiran\System\Models\Policies\PamRolePolicy;
use Weiran\System\Setting\Repository\SettingRepository;

/**
 * @property $listens;
 */
class ServiceProvider extends WeiranServiceProvider
{
    use WeiranTrait;

    protected array $listens = [
        // laravel
        AuthLoginEvent::class           => [

        ],
        PermissionInitEvent::class      => [
            Listeners\PermissionInit\InitToDbListener::class,
        ],
        WeiranOptimized::class          => [
            Listeners\WeiranOptimized\ClearCacheListener::class,
        ],
        LoginTokenPassedEvent::class    => [
            Listeners\LoginTokenPassed\SsoListener::class,
        ],
        PamLogoutEvent::class           => [
            Listeners\PamLogout\SsoListener::class,
        ],
        TokenRenewEvent::class          => [
            Listeners\TokenRenew\TokenRenewListener::class,
        ],
        PamPasswordModifiedEvent::class => [
            Listeners\PamPasswordModified\SsoListener::class,
        ],

        QueryExecuted::class            => [
            Listeners\QueryExecuted\LogListener::class,
        ],

        // system
        Events\LoginSuccessEvent::class => [
            Listeners\LoginSuccess\UpdatePasswordHashListener::class,
            Listeners\LoginSuccess\LogListener::class,
            Listeners\LoginSuccess\UpdateLastLoginListener::class,
        ],
    ];

    protected array $policies = [
        PamRole::class    => PamRolePolicy::class,
        PamAccount::class => PamAccountPolicy::class,
    ];

    /**
     * Bootstrap the module services.
     * @return void
     * @throws ModuleNotFoundException
     */
    public function boot(): void
    {
        parent::boot('weiran.system');

        $this->bootConfigs();
    }

    /**
     * Register the module services.
     * @return void
     */
    public function register(): void
    {
        // 配置文件
        $this->mergeConfigFrom(dirname(__DIR__) . '/resources/config/system.php', 'weiran.system');

        $this->app->register(Http\MiddlewareServiceProvider::class);
        $this->app->register(Http\RouteServiceProvider::class);

        $this->registerConsole();

        $this->registerAuth();

        $this->registerSchedule();

        $this->registerContracts();
    }

    public function provides(): array
    {
        return [
            'weiran.system.setting', SettingContract::class,
            'weiran.system.uploader', FileContract::class
        ];
    }

    private function registerSchedule(): void
    {
        app('events')->listen(WeiranSchedule::class, function (Schedule $schedule) {

            $schedule->command('system:user', ['auto_enable'])
                ->everyFifteenMinutes()->appendOutputTo($this->consoleLog());
            $schedule->command('system:user', ['clear_log'])
                ->dailyAt('04:00')->appendOutputTo($this->consoleLog());
            // 每天清理一次
            $schedule->command('system:user', ['clear_expired'])
                ->dailyAt('06:00')->appendOutputTo($this->consoleLog());
            $schedule->command('system:op', ['gen-secret'])
                ->dailyAt('06:00')->appendOutputTo($this->consoleLog());
        });
    }

    /**
     * register rbac and alias
     */
    private function registerContracts(): void
    {
        $this->app->bind('weiran.system.api_sign', function () {
            /** @var ApiSignContract $signProvider */
            $signProvider = config('weiran.system.api_sign_provider') ?: DefaultApiSignProvider::class;
            return new $signProvider();
        });
        $this->app->alias('weiran.system.api_sign', ApiSignContract::class);


        $this->app->bind('weiran.system.password', function () {
            $pwdClass = config('weiran.system.password_provider') ?: DefaultPasswordProvider::class;
            return new $pwdClass();
        });
        $this->app->alias('weiran.system.password', PasswordContract::class);


        /* 文件上传提供者
         * ---------------------------------------- */
        $this->app->bind('weiran.system.uploader', function () {
            $uploadType = sys_setting('weiran-system::picture.save_type');
            $hooks      = sys_hook('weiran.system.upload_type');
            if (!$uploadType) {
                $uploadType = 'default';
            }
            $uploader      = $hooks[$uploadType];
            $uploaderClass = $uploader['provider'] ?? DefaultFileProvider::class;
            return new $uploaderClass();
        });
        $this->app->alias('weiran.system.uploader', FileContract::class);


        /* 设置配置
         * ---------------------------------------- */
        $this->app->bind('weiran.system.setting', function () {
            return new SettingRepository();
        });
        $this->app->alias('weiran.system.setting', SettingRepository::class);
        $this->app->alias('weiran.system.setting', SettingContract::class);

    }

    private function registerConsole(): void
    {
        $this->commands([
            Commands\UserCommand::class,
            Commands\InstallCommand::class,
            Commands\BanCommand::class,
            Commands\BanInitCommand::class,
            Commands\OpCommand::class,
        ]);
    }

    private function registerAuth(): void
    {
        app('auth')->provider('pam.web', function () {
            return new WebProvider(PamAccount::class);
        });
        app('auth')->provider('pam.backend', function () {
            return new BackendProvider(PamAccount::class);
        });
        app('auth')->provider('pam', function () {
            return new PamProvider(PamAccount::class);
        });
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws SettingKeyNotMatchException
     * @throws JsonException
     * @throws SettingValueOutOfRangeException
     * @throws NotFoundExceptionInterface
     */
    private function bootConfigs(): void
    {
        config([
            'mail.driver'       => sys_setting('weiran-system::mail.driver') ?: config('mail.driver'),
            'mail.encryption'   => sys_setting('weiran-system::mail.encryption') ?: config('mail.encryption'),
            'mail.port'         => sys_setting('weiran-system::mail.port') ?: config('mail.port'),
            'mail.host'         => sys_setting('weiran-system::mail.host') ?: config('mail.host'),
            'mail.from.address' => sys_setting('weiran-system::mail.from') ?: config('mail.from.address'),
            'mail.from.name'    => sys_setting('weiran-system::mail.from') ?: config('mail.from.name'),
            'mail.username'     => sys_setting('weiran-system::mail.username') ?: config('mail.username'),
            'mail.password'     => sys_setting('weiran-system::mail.password') ?: config('mail.password'),
        ]);

        // 注入标题用于 SEO
        config([
            'weiran.framework.title'       => sys_setting('weiran-system::site.name'),
            'weiran.framework.description' => sys_setting('weiran-system::site.description'),
        ]);
    }
}