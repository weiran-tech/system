<?php

declare(strict_types = 1);

use Carbon\Carbon;
use Detection\Exception\MobileDetectException;
use Detection\MobileDetect;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Weiran\Core\Classes\Contracts\SettingContract;
use Weiran\Framework\Helper\StrHelper;
use Weiran\Framework\Helper\TimeHelper;
use Weiran\Framework\Helper\UtilHelper;
use Weiran\System\Classes\WeiranSystemDef;
use Weiran\System\Models\PamAccount;

if (!function_exists('sys_setting')) {
    /**
     * Get System Setting
     *
     * @param null $default
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    function sys_setting(string $key, $default = null): mixed
    {
        return app(SettingContract::class)->get($key, $default);
    }
}

if (!function_exists('sys_setting_set')) {
    /**
     * Get System Setting
     */
    function sys_setting_set(string|array $key, mixed $default = null): mixed
    {
        return app(SettingContract::class)->set($key, $default);
    }
}

if (!function_exists('sys_gen_order')) {
    /**
     * 生成订单号
     */
    function sys_gen_order(string $prefix = ''): string
    {
        try {
            $sequence = random_int(1000, 9999);
        }
        catch (Exception) {
            $sequence = Str::random(4);
        }
        $current = Carbon::now()->format('YmdHis');

        return sprintf('%s%s%s%s', strtoupper($prefix), $current, TimeHelper::micro(), sprintf("%'.04d", $sequence));
    }
}

if (!function_exists('sys_order_prefix')) {
    /**
     * 生成订单号
     */
    function sys_order_prefix(string $order_no): string
    {
        if (preg_match('/^([a-zA-Z]+)\d*/i', $order_no, $matches)) {
            return $matches[1];
        }

        return 'other';
    }
}

if (!function_exists('sys_trans')) {
    /**
     * translate line
     */
    function sys_trans(string $line, array $replace = []): string
    {
        foreach ($replace as $key => $value) {
            $line = str_replace(
                [':' . $key, ':' . Str::upper($key), ':' . Str::ucfirst($key)],
                [$value, Str::upper($value), Str::ucfirst($value)],
                $line
            );
        }

        return $line;
    }
}

if (!function_exists('sys_seo')) {
    function sys_seo(...$args): void
    {
        [$title, $description] = parse_seo($args);
        // 赋值
        $title       = $title ? $title . '-' . config('weiran.framework.title') : config('weiran.framework.title');
        $description = $description ?: config('weiran.framework.description');
        View::share([
            '_title'       => $title,
            '_description' => $description,
        ]);
    }
}

if (!function_exists('sys_str_unique')) {
    /**
     * 获取通过 ',' 间隔的唯一字串, 去除空值
     */
    function sys_str_unique(string $current, string $add): string
    {
        // 追加
        $current .= ',' . $add;
        // 去重
        $arr = explode(',', $current);

        return collect($arr)->sort()->unique()->filter(function ($item) {
            return $item;
        })->sort()->implode(',');
    }
}

if (!function_exists('sys_str_to_json')) {
    /**
     * 字串转换为json
     *
     * @throws JsonException
     *
     * @deprecated 1.0 使用原生的 json_decode
     */
    function sys_str_to_json($string): array
    {
        if (is_object($string)) {
            return json_decode(json_encode($string, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
        }
        if (UtilHelper::isJson($string)) {
            return json_decode($string, true, 512, JSON_THROW_ON_ERROR);
        }

        return [];
    }
}

if (!function_exists('sys_array_to_json')) {
    /**
     * 字串转换为json
     *
     * @throws JsonException
     *
     * @deprecated 1.0 使用原生的 json_encode
     */
    function sys_array_to_json($string): string
    {
        return json_encode($string, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }
}

if (!function_exists('sys_is_pjax')) {
    /**
     * 检测是否是 pjax 请求
     *
     * @deprecated 1.0 直接使用框架的 pjax
     */
    function sys_is_pjax(): bool
    {
        return Request::pjax();
    }
}

if (!function_exists('sys_get')) {
    /**
     * 获取 data 中的数据
     * 支持批量获取
     *
     * @param array|object      $data
     * @param string|null|array $key
     * @param string|null|mixed $default
     *
     * @return null|array|string
     *
     * @deprecated 1.0 使用原生有价值的来获取, 这个函数比较隐形, 不要使用这个
     */
    function sys_get($data, $key, $default = ''): mixed
    {
        if (is_array($key)) {
            $arr = Arr::only($data, $key);

            return array_map(function ($value) {
                if (empty($value)) {
                    return '';
                }
                if (is_string($value)) {
                    return trim($value);
                }

                return $value;
            }, $arr);
        }
        $value = data_get($data, $key, $default);
        $value = is_null($value) ? $default : $value;
        if (is_string($value)) {
            return trim($value);
        }

        return $value;
    }
}

if (!function_exists('sys_parent_id')) {
    /**
     * 父级用户
     */
    function sys_parent_id(int|string|PamAccount $pam): int
    {
        static $rel;

        if (!$pam) {
            return 0;
        }

        if (is_string($pam) && !is_numeric($pam)) {
            return 0;
        }

        /*
        |--------------------------------------------------------------------------
        | 定义缓存的策略
        |--------------------------------------------------------------------------
        | 分片策略：每 10000 个用户一个分片
        | 缓存策略: 设置 7 天过期时间
        */
        $shardSize     = 10000;
        $cachedSeconds = 60 * 60 * 24 * 7;

        $pamId = ($pam instanceof PamAccount) ? $pam->id : (int) $pam;

        // 优先从静态缓存中获取
        if (isset($rel[$pamId])) {
            return $rel[$pamId];
        }

        $shardIndex = (string) floor($pamId / $shardSize);
        $cacheKey   = WeiranSystemDef::ckPamRelParent($shardIndex);

        // 从 Redis Hash 分片中获取
        $cached = sys_tag('weiran-system')->hGet($cacheKey, (string) $pamId);

        if ($cached !== null) {
            $rel[$pamId] = (int) $cached;

            return $rel[$pamId];
        }

        // 从数据库加载
        $pamObj = PamAccount::whereKey($pamId)->first(['id', 'parent_id']);
        if (!$pamObj) {
            return 0;
        }

        // 计算并缓存结果
        $parentId    = $pamObj->parent_id ?: $pamObj->id;
        $rel[$pamId] = $parentId;

        // 存储到 Redis Hash 分片
        sys_tag('weiran-system')->hSet($cacheKey, (string) $pamId, (string) $parentId);
        sys_tag('weiran-system')->expire($cacheKey, $cachedSeconds);

        return $rel[$pamId] ?? 0;
    }
}

if (!function_exists('sys_url')) {
    /**
     * URL生成
     *
     * @param string|array $key   url 参数
     * @param null|string  $value 值
     * @param bool         $root  是否生成根地址
     *
     * @return string
     */
    function sys_url($key, $value = null, $root = false)
    {
        if ($root) {
            return '?' . $key . '=' . $value;
        }
        $input = app('request')->all();
        // 字串
        if (is_string($key)) {
            if ($value === null) {
                unset($input[$key]);
            }
            else {
                $input[$key] = $value;
            }
        }

        // array
        if (is_array($key)) {
            foreach ($key as $_key => $_val) {
                if ($_val === null) {
                    unset($input[$_key]);
                }
                else {
                    $input[$_key] = $_val;
                }
            }
        }

        if (is_array($input)) {
            foreach ($input as $_key => $_val) {
                if (Str::startsWith($_key, '_')) {
                    unset($input[$_key]);
                }
            }
        }

        return '?' . http_build_query($input);
    }
}

if (!function_exists('sys_is_mobile')) {
    /**
     * 检测是否是 Mobile
     *
     * @throws MobileDetectException
     */
    function sys_is_mobile(): bool
    {
        $useragent = Request::userAgent();

        $detect = new MobileDetect();
        $detect->setUserAgent($useragent);

        return $detect->isMobile();
    }
}

if (!function_exists('sys_key_trim')) {
    /**
     * 对于Key来去掉 --------- PRIVATE KEY ----------
     */
    function sys_key_trim(string $key): string
    {
        return StrHelper::trimSpace(preg_replace('/-----.*?-----/', '', $key));
    }
}

if (!function_exists('sys_api_demo')) {
    /**
     * 使用万能密钥则是测试模式
     *
     * @deprecated 1.0
     *
     * @see        sys_api_in_super_mode()
     */
    function sys_api_demo(): bool
    {
        return ((string) Request::input('_weiran_secret')) === config('weiran.system.secret');
    }
}

if (!function_exists('sys_api_in_super_mode')) {
    /**
     * 主要的密钥模式
     *
     * @since 1.0
     */
    function sys_api_in_super_mode(): bool
    {
        return ((string) Request::input('_weiran_secret')) === config('weiran.system.secret');
    }
}

if (!function_exists('sys_content_trim')) {
    /**
     * 清空word 代码
     *
     * @param string $content        内容
     * @param string $allowable_tags 允许保留的标签
     */
    function sys_content_trim(string $content, string $allowable_tags = 'p,img'): string
    {
        mb_regex_encoding('UTF-8');
        // replace MS special characters first
        $search  = ['/‘/u', '/’/u', '/“/u', '/”/u', '/—/u'];
        $replace = ['\'', '\'', '"', '"', '-'];
        $content = preg_replace($search, $replace, $content);
        // make sure _all_ html entities are converted to the plain ascii equivalents - it appears
        // in some MS headers, some html entities are encoded and some aren't
        $content = html_entity_decode($content, ENT_QUOTES, 'UTF-8');
        // try to strip out any C style comments first, since these, embedded in html comments, seem to
        // prevent strip_tags from removing html comments (MS Word introduced combination)
        if (mb_stripos($content, '/*') !== false) {
            $content = (string) mb_eregi_replace('#/\*.*?\*/#s', '', $content, 'm');
        }
        // introduce a space into any arithmetic expressions that could be caught by strip_tags so that they won't be
        // '<1' becomes '< 1'(note: somewhat application specific)
        $content = preg_replace(['/<([0-9]+)/'], ['< $1'], $content);

        $content = strip_tags($content, $allowable_tags);
        // eliminate extraneous whitespace from start and end of line, or anywhere there are two or more spaces, convert it to one
        $content = preg_replace(['/^\s\s+/', '/\s\s+$/', '/\s\s+/u'], ['', '', ' '], $content);
        // strip out inline css and simplify style tags
        $search  = ['#<(strong|b)[^>]*>(.*?)</(strong|b)>#isu', '#<(em|i)[^>]*>(.*?)</(em|i)>#isu', '#<u[^>]*>(.*?)</u>#isu'];
        $replace = ['<b>$2</b>', '<i>$2</i>', '<u>$1</u>'];
        $content = preg_replace($search, $replace, $content);

        // on some of the ?newer MS Word exports, where you get conditionals of the form 'if gte mso 9', etc., it appears
        // that whatever is in one of the html comments prevents strip_tags from eradicating the html comment that contains
        // some MS Style Definitions - this last bit gets rid of any leftover comments */
        $num_matches = preg_match_all('/<!--/u', $content);
        if ($num_matches) {
            $content = preg_replace('/<!--(.)*-->/su', '', $content);
        }

        return preg_replace('/mso-([a-z-A-Z]*:\s?[a-z-A-Z]*;?)/i', '', $content);
    }
}

if (!function_exists('sys_is_demo')) {
    /**
     * 是否是 Demo 模式
     */
    function sys_is_demo(): bool
    {
        return config('weiran.system.demo');
    }
}
