<?php

declare(strict_types = 1);

namespace Weiran\System\Http;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Define your route model bindings, pattern filters, etc.
     * @return void
     */
    public function boot()
    {
        $this->routes(function () {
            $this->mapApiRoutes();
        });
    }


    /**
     * Define the "api" routes for the module.
     * These routes are typically stateless.
     * @return void
     */
    protected function mapApiRoutes(): void
    {
        // Api V1 版本
        Route::group([
            'prefix' => 'api_v1/system',
        ], function () {
            require_once __DIR__ . '/Routes/api_v1_web.php';
        });
    }
}