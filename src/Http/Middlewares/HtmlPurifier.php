<?php

declare(strict_types = 1);

namespace Weiran\System\Http\Middlewares;

use Closure;
use File;
use HTMLPurifier_Config;
use Illuminate\Http\Request;
use Storage;

/**
 * Html净化
 * stip : 这里需要注意对于无存储的验证性的数据不进行过滤, 例如用户输入密码中的特殊符号, 这里的数据不进行展示
 */
class HtmlPurifier
{
    public function handle(Request $request, Closure $next)
    {
        $Storage   = Storage::disk('storage');
        $cachePath = $Storage->path('html_purifier/');
        if (!File::exists($cachePath)) {
            File::makeDirectory($cachePath);
        }
        $config = HTMLPurifier_Config::createDefault();
        $config->set('Cache.SerializerPath', $cachePath);
        $Purifier = new \HTMLPurifier($config);
        $input    = $request->all();
        array_walk_recursive($input, static function (&$input) use ($Purifier) {
            $input = $Purifier->purify($input);
        });
        $request->merge($input);
        return $next($request);
    }
}