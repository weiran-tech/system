<?php

declare(strict_types=1);

namespace Weiran\System\Http\Web\ApiV1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Request as RequestFacade;
use OpenApi\Attributes as OA;
use Throwable;
use Weiran\Framework\Classes\Resp;
use Weiran\System\Classes\Contracts\FileContract;
use Weiran\System\Classes\File\DefaultFileProvider;
use Weiran\System\Http\OpenApi\BaseResponseBody;
use Weiran\System\Http\Web\ApiV1\Upload\UploadFileRequest;
use Weiran\System\Http\Web\ApiV1\Upload\UploadFileResponseBody;
use Weiran\System\Http\Web\ApiV1\Upload\UploadImageBase64Request;
use Weiran\System\Http\Web\ApiV1\Upload\UploadImageRequest;
use Weiran\System\Http\Web\ApiV1\Upload\UploadImageResponseBody;
use Weiran\System\Http\Web\ApiV1\Upload\UploadImageWangEditorResponseBody;

/**
 * 图片处理控制器
 */
class UploadController extends JwtApiController
{
    #[OA\Post(
        path: '/api/web/system/v1/upload/image',
        summary: '图片上传',
        description: '支持 multipart/form-data 文件上传和 base64 JSON 上传; 当 from=wang-editor 时返回编辑器专用响应结构',
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                new OA\MediaType(
                    mediaType: 'multipart/form-data',
                    schema: new OA\Schema(ref: UploadImageRequest::class)
                ),
                new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(ref: UploadImageBase64Request::class)
                ),
            ],
        ),
        tags: ['System'],
        responses: [
            new OA\Response(
                response: 200,
                description: '图片上传',
                content: new OA\JsonContent(
                    oneOf: [
                        new OA\Schema(ref: BaseResponseBody::class),
                        new OA\Schema(ref: UploadImageResponseBody::class),
                        new OA\Schema(ref: UploadImageWangEditorResponseBody::class),
                    ]
                )
            ),
        ]
    )]
    public function image(UploadImageRequest $request): Response|JsonResponse|RedirectResponse
    {
        $type = $request->getType();
        $folder = $request->getFolder();
        $watermark = $request->getWatermark();
        $from = $request->getFrom();
        if (sys_is_demo()) {
            return $this->demo();
        }

        /** @var DefaultFileProvider $file */
        $file = app(FileContract::class);
        $file->setFolder($folder);

        /* 图片水印
         * ---------------------------------------- */
        $watermark && $file->enableWatermark();

        /* 图片上传大小限制,过大则需要手动进行缩放
         * ---------------------------------------- */
        $district = config('weiran.system.upload_image_district');
        if (isset($district[$folder]) && (int) $district[$folder] > 0) {
            $file->setResizeDistrict((int) $district[$folder]);
        }

        $urls = [];
        if ($type === 'form') {
            $file->setExtension(['jpg', 'png', 'gif', 'jpeg', 'webp', 'bmp', 'heic', 'mp4', 'rm', 'rmvb', 'wmv']);
            $image = RequestFacade::file('image');
            if ($image instanceof UploadedFile) {
                $image = [$image];
            }

            foreach ($image as $_img) {
                if (is_null($_img)) {
                    return Resp::error('图片内容为空, 请检查是否上传图片或者支持类型是否正确');
                }
                if (! $_img->isValid()) {
                    return Resp::error('文件未正确上传, 请重试');
                }
                if ($file->saveFile($_img)) {
                    $urls[] = $file->getUrl();
                } else {
                    return Resp::error($file->getError());
                }
            }
        } elseif ($type === 'base64') {
            $image = (array) $request->getImage();
            $file->setQuality(85);
            foreach ($image as $_img) {
                $data = array_filter(explode(',', $_img));
                if (count($data) >= 2) {
                    [$mime_info, $_img] = $data;

                    $slashes_index = strpos($mime_info, '/');
                    $semicolon_index = strpos($mime_info, ';');

                    $length = $semicolon_index - $slashes_index - 1;
                    $mime_type = substr($mime_info, $slashes_index + 1, $length);
                    $file->setMimeType($mime_type);
                } elseif (count($data) === 1) {
                    $_img = $data[0];
                    $file->setMimeType('');
                } else {
                    continue;
                }

                $content = base64_decode($_img);
                try {
                    if ($file->saveInput($content)) {
                        $urls[] = $file->getUrl();
                    }
                } catch (Throwable) {
                    continue;
                }
            }
        }

        // 上传图
        if (count($urls)) {
            if ($from === 'wang-editor') {
                $data = collect($urls)->map(function ($url) {
                    return [
                        'url' => $url,
                        'alt' => '',
                        'href' => '',
                    ];
                });

                return response()->json([
                    'errno' => 0,
                    'data' => $data->toArray(),
                ]);
            }

            return Resp::success('上传成功', [
                'url' => $urls,
            ]);
        }
        if ($from === 'wang-editor') {
            return response()->json([
                'errno' => 1,
                'message' => $file->getError(),
            ]);
        }

        return Resp::error($file->getError());
    }

    #[OA\Post(
        path: '/api/web/system/v1/upload/file',
        description: '这里的文件上传支持音视频, 文件',
        summary: '上传文件',
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                new OA\MediaType(
                    mediaType: 'multipart/form-data',
                    schema: new OA\Schema(ref: UploadFileRequest::class)
                ),
            ]
        ),
        tags: ['System'],
        responses: [
            new OA\Response(
                response: 200,
                description: '上传成功',
                content: new OA\JsonContent(ref: UploadFileResponseBody::class)
            ),
        ]
    )]
    public function file(UploadFileRequest $request): Response|JsonResponse|RedirectResponse
    {
        $type = $request->getType();
        $folder = $request->getFolder();

        if (sys_is_demo()) {
            return $this->demo();
        }

        $Uploader = app(FileContract::class);
        $Uploader->setType($type);
        if ($folder) {
            $Uploader->setFolder($folder);
        }
        $urls = [];

        $file = RequestFacade::file('file');
        if (! is_array($file)) {
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

    private function demo(): Response|JsonResponse|RedirectResponse
    {
        return Resp::success('上传成功', [
            'url' => [
                'https://i.wulicode.com/img/400',
            ],
        ]);
    }
}
