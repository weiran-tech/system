<?php

declare(strict_types = 1);

namespace Weiran\System\Http\Request\ApiV1;

use Weiran\Framework\Classes\Resp;
use Weiran\Framework\Helper\UtilHelper;
use Weiran\System\Classes\Contracts\FileContract;
use Weiran\System\Classes\File\DefaultFileProvider;
use Request;
use Throwable;
use Validator;

/**
 * 图片处理控制器
 */
class UploadController extends JwtApiController
{

    /**
     * @api                   {post} /api_v1/system/upload/image [Sys]图片上传
     * @apiDescription        图片上传
     * @apiVersion            1.0.0
     * @apiName               SysUploadImage
     * @apiGroup              Poppy
     * @apiQuery {string}     image         图片内容(支持多张/单张上传)
     * @apiQuery {string}     [type]        上传图片的类型 (form|表单(默认),base64,url)
     * @apiQuery {string}     [image_type]  图片图片存储类型(default|默认), 不同的图片存储到不同的文件夹下
     * @apiQuery {string}     [from]        上传来源,根据不同来源返回不同的格式 (wang-editor)
     * @apiQuery {string}     [watermark]   是否开启水印(1:开启)
     */
    public function image()
    {
        $type       = input('type', 'form');
        $image_type = input('image_type', 'default');
        $watermark  = input('watermark');

        $all               = Request::all();
        $all['image_type'] = $image_type ?: 'default';
        $all['type']       = $type;

        if (!isset($all['image']) || !$all['image']) {
            return Resp::error('图片内容必须');
        }

        $validator = Validator::make($all, [
            'type' => 'required|in:form,base64,url',
        ], [], [
            'type' => '上传图片的类型',
        ]);
        if ($validator->fails()) {
            return Resp::error($validator->messages());
        }

        if (sys_is_demo()) {
            return $this->demo();
        }

        /** @var DefaultFileProvider $Image */
        $Image = app(FileContract::class);
        $Image->setFolder($image_type);

        if ($watermark) {
            $Image->enableWatermark();
        }

        /* 图片上传大小限制,过大则需要手动进行缩放
         * ---------------------------------------- */
        $district = config('weiran.system.upload_image_district');
        if (isset($district[$image_type]) && (int) $district[$image_type] > 0) {
            $Image->setResizeDistrict((int) $district[$image_type]);
        }

        $urls = [];
        if ($type === 'form') {
            $Image->setExtension(['jpg', 'png', 'gif', 'jpeg', 'webp', 'bmp', 'heic', 'mp4', 'rm', 'rmvb', 'wmv']);
            $image = Request::file('image');
            if (!is_array($image)) {
                $image = [$image];
            }

            foreach ($image as $_img) {
                if (is_null($_img)) {
                    return Resp::error('图片内容为空, 请检查是否上传图片或者支持类型是否正确');
                }
                if ($Image->saveFile($_img)) {
                    $urls[] = $Image->getUrl();
                }
                else {
                    return Resp::error($Image->getError());
                }
            }
        }
        elseif ($type === 'base64') {
            $image = input('image');

            if (!is_array($image) && UtilHelper::isJson($image)) {
                $image = json_decode($image, true);
            }
            if (!is_array($image)) {
                $image = [$image];
            }
            $Image->setQuality(85);
            foreach ($image as $_img) {
                $data = array_filter(explode(',', $_img));
                if (count($data) >= 2) {
                    [$mime_info, $_img] = $data;

                    $slashes_index   = strpos($mime_info, '/');
                    $semicolon_index = strpos($mime_info, ';');

                    $length    = $semicolon_index - $slashes_index - 1;
                    $mime_type = substr($mime_info, $slashes_index + 1, $length);
                    $Image->setMimeType($mime_type);
                }
                else if (count($data) === 1) {
                    $_img = $data[0];
                    $Image->setMimeType('');
                }
                else {
                    continue;
                }

                $content = base64_decode($_img);
                try {
                    if ($Image->saveInput($content)) {
                        $urls[] = $Image->getUrl();
                    }
                } catch (Throwable $e) {
                    continue;
                }
            }
        }
        elseif ($type === 'url') {
            $image = input('image');
            if (!is_array($image)) {
                $image = [$image];
            }
            $Image->setQuality(85);
            foreach ($image as $_img) {
                try {
                    if ($Image->saveInput($_img)) {
                        $urls[] = $Image->getUrl();
                    }
                    else {
                        return Resp::error($Image->getError());
                    }
                } catch (Throwable $e) {
                    return Resp::error($e->getMessage());
                }
            }
        }

        $from = input('from');
        // 上传图
        if (count($urls)) {
            if ($from === 'wang-editor') {
                $data = collect($urls)->map(function ($url) {
                    return [
                        'url'  => $url,
                        'alt'  => '',
                        'href' => '',
                    ];
                });
                return response()->json([
                    'errno' => 0,
                    'data'  => $data->toArray(),
                ]);
            }
            return Resp::success('上传成功', [
                'url' => $urls,
            ]);
        }
        if ($from === 'wang-editor') {
            return response()->json([
                'errno'   => 1,
                'message' => $Image->getError(),
            ]);
        }
        return Resp::error($Image->getError());
    }

    /**
     * @api                   {post} /api_v1/system/upload/file [Sys]文件上传
     * @apiDescription        上传文件, 这里的文件上传支持音视频, 不支持图片
     * @apiVersion            1.0.0
     * @apiName               SysUploadFile
     * @apiGroup              Poppy
     * @apiQuery {string}     file        内容
     * @apiQuery {string}     type        上传类型(audio|音频;video|视频;images|图片;file|文件上传)
     * @apiQuery {string}     [folder]    (4.0) 文件存储目录
     * @apiQuery {string}     [ext]       上传限制扩展(后台进行限制), 多个使用 ',' 分隔, 默认是 后台进行限制
     * @apiQuery {string}     [district]  图片大小限制(最短边, 默认是 1080)
     */
    public function file()
    {
        $type     = input('type', 'audio');
        $ext      = input('ext', '');
        $district = (int) input('district', 1080);
        $folder   = input('folder', '');

        $input = input();

        $validator = Validator::make($input, [
            'file' => 'required',
            'type' => 'required|in:audio,video,images,file',
        ], [], [
            'file' => '上传文件',
            'type' => '类型',
        ]);
        if ($validator->fails()) {
            return Resp::error($validator->messages());
        }

        if (sys_is_demo()) {
            return $this->demo();
        }

        $Uploader = app(FileContract::class);
        $Uploader->setType($type);
        if ($folder) {
            $Uploader->setFolder($folder);
        }
        $urls = [];
        /*if ($ext) {
            $extensions = explode(',', $ext);
            $Uploader->setExtension($extensions);
        }*/

        // 默认图片压缩到 1080 短边压缩
        if ($type === 'images') {
            $Uploader->setResizeDistrict($district);
        }
        $file = Request::file('file');
        if (!is_array($file)) {
            $file = [$file];
        }

        foreach ($file as $_file) {
            if ($Uploader->saveFile($_file)) {
                $urls[] = $Uploader->getUrl();
            }
        }

        // 上传图
        if (count($urls)) {
            return Resp::success('上传成功', [
                'url' => $urls,
            ]);
        }

        return Resp::error($Uploader->getError());
    }


    private function demo()
    {
        try {
            return Resp::success('上传成功', [
                'url' => [
                    'https://jdc.jd.com/img/400',
                ],
            ]);
        } catch (Throwable $e) {
            return Resp::error('操作失败');
        }
    }
}