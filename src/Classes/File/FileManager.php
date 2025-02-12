<?php

declare(strict_types = 1);

namespace Weiran\System\Classes\File;

use Illuminate\Support\Str;
use Weiran\System\Classes\Contracts\FileContract;

/**
 * 图片上传类
 */
class FileManager
{

    public const TYPE_IMAGES = 'images';
    public const TYPE_FILE   = 'file';
    public const TYPE_VIDEO  = 'video';
    public const TYPE_AUDIO  = 'audio';

    /**
     * 获取可用扩展
     * @param string $type
     * @return array
     */
    public static function kvExt(string $type): array
    {
        $desc = [
            self::TYPE_IMAGES => ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg'],
            self::TYPE_FILE   => [
                'html', 'htm',
                'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'pps', 'potx', 'pot',
                'rtf', 'odt', 'pages', 'ai', 'dxf', 'ttf', 'tiff', 'tif', 'wmf', 'eps',
                'txt', 'md', 'csv', 'nfo', 'ini', 'json', 'js', 'css', 'ts', 'sql',
                'zip', 'rp', 'rplib', 'svga',
                'pdf',
                'apk', 'ipa',
            ],
            self::TYPE_VIDEO  => ['mp4', 'rm', 'rmvb', 'wmv', 'webm', 'mpg', 'mov', '3gp'],
            self::TYPE_AUDIO  => ['mp3', 'm4a', 'wav', 'aac'],
        ];
        return kv($desc, $type);
    }

    /**
     * 获取描述
     * @param string $type
     * @return string
     */
    public static function kvDesc(string $type): string
    {
        $desc = [
            self::TYPE_IMAGES => '请选择图片',
            self::TYPE_AUDIO  => '请选择音频文件',
            self::TYPE_VIDEO  => '请选择视频文件',
            self::TYPE_FILE   => '选择文件',
        ];
        return kv($desc, $type);
    }


    /**
     * 上传的前缀地址
     * @return string
     */
    public static function prefix(): string
    {
        return app(FileContract::class)->getReturnUrl();
    }


    /**
     * 规则预览
     * @return array
     * @since 4.2
     */
    public static function previewRules(): array
    {
        $strRules = preg_replace('/\s+/', ';', sys_setting('weiran-system::picture.preview_rule'));
        $arrRules = explode(';', $strRules);
        $rules    = [];
        if (count($arrRules)) {
            foreach ($arrRules as $rule) {
                if (Str::contains($rule, '|')) {
                    $arrRule = explode('|', $rule);
                    if (isset($arrRule[0], $arrRule[1])) {
                        $rules[$arrRule[0]][] = $arrRule[1];
                    }
                }
            }
        }
        return $rules;
    }

    public static function previewImage($url, $size = 40)
    {
        $strRules = preg_replace('/\s+/', ';', sys_setting('weiran-system::picture.preview_rule'));
        $arrRules = explode(';', $strRules);
        $platform = '';
        if (count($arrRules)) {
            foreach ($arrRules as $rule) {
                if ($platform) {
                    continue;
                }
                if (Str::contains($rule, '|')) {
                    $arrRule = explode('|', $rule);
                    if (isset($arrRule[0], $arrRule[1]) && Str::contains($url, $arrRule[1])) {
                        $platform = $arrRule[0];
                    }
                }
            }
        }
        switch ($platform) {
            case 'aliyun':
                if (!Str::contains($url, '?x-oss-process')) {
                    $url = "{$url}?x-oss-process=image/resize,l_{$size}";
                }
                break;
            case 'tencent':
            case 'qiniu':
                if (!Str::contains($url, '?imageView2')) {
                    $url = "{$url}?imageView2/0/w/{$size}";
                }
                break;
            case 'huawei':
                if (!Str::contains($url, '?x-image-process')) {
                    $url = "{$url}?x-image-process=image/resize,l_{$size}";
                }
                break;
        }
        return $url;
    }


    /**
     * 重新定义大小
     * @param int      $width 原始宽度
     * @param int      $height 原始高度
     * @param int      $min_district 最小限制值
     * @param int|null $max_district 最大限制值
     * @return array
     */
    public static function resizedSize(int $width, int $height, int $min_district, ?int $max_district): array
    {
        $min    = min($width, $height);
        $max    = max($width, $height);
        $resize = true;
        // horizontal [----], vertical []
        $direction = $min === $height ? 'horizontal' : 'vertical';

        // 短边需要压缩或者长边需要压缩
        $compressMin = $min >= $min_district;
        $compressMax = ($max_district && $max > $max_district);

        // 长短均需要压缩
        if ($compressMin && $compressMax) {
            $rateMin = $min_district / $min;
            $rateMax = $max_district / $max;
            $minRate = min($rateMax, $rateMin);
            if ($direction === 'horizontal') {
                // 以小的比值作为伸缩比
                $r_height = $min_district;
                $r_width  = (int) round($max * $minRate);
            }
            else {
                // 以小的比值作为伸缩比
                $r_width  = $min_district;
                $r_height = (int) round($max * $minRate);
            }
        }

        // 压缩短边
        else if ($compressMin) {
            $r_width  = $direction === 'horizontal' ? null : $min_district;
            $r_height = $direction === 'vertical' ? null : $min_district;
        }

        // 压缩长边
        else if ($compressMax) {
            $r_width  = $direction === 'horizontal' ? $max_district : null;
            $r_height = $direction === 'vertical' ? $max_district : null;
        }
        else {
            $r_width  = $width;
            $r_height = $height;
            $resize   = false;
        }
        return ['width' => $r_width, 'height' => $r_height, 'resize' => $resize];
    }
}