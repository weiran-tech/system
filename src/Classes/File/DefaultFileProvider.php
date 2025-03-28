<?php

declare(strict_types = 1);

namespace Weiran\System\Classes\File;

use Carbon\Carbon;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Str;
use Intervention\Image\Constraint;
use Intervention\Image\Exception\NotReadableException;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use Weiran\Framework\Classes\Traits\AppTrait;
use Weiran\Framework\Exceptions\ApplicationException;
use Weiran\Framework\Helper\FileHelper;
use Weiran\Framework\Helper\UtilHelper;
use Weiran\System\Classes\Contracts\FileContract;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * 图片上传类
 */
class DefaultFileProvider implements FileContract
{
    use AppTrait;

    /**
     * @var string 目标路径
     */
    protected string $destination = '';

    /**
     * @var string 目标磁盘
     */
    protected string $disk = 'public';

    /**
     * 是否启用水印
     * @var bool
     */
    protected bool $watermark = false;

    /**
     * @var string 文件夹
     */
    private string $folder;

    /**
     * @var string 返回地址
     */
    private string $returnUrl;

    /**
     * @var array 允许上传的扩展
     */
    private array $allowedExtensions = ['zip'];

    /**
     * @var int 默认图片质量
     */
    private int $quality = 70;

    /**
     * 短边限制
     * @var int
     */
    private int $resizeDistrict = 1920;


    /**
     * 长边限制
     * @var int|null
     */
    protected ?int $resizeLongDistrict = null;

    /**
     * @var string 图片mime类型
     */
    private string $mimeType = '';

    /**
     * 是否强制设置目录-这样目录是不变的
     * 不能适用于连续上传图片场景，只适用于明确地址的图片
     * @var bool
     */
    private bool $isForceSetDestination = false;


    public function __construct()
    {
        $this->folder    = (is_production() ? '' : 'dev/') . 'uploads';
        $this->returnUrl = config('app.url') . '/';
    }


    public function setFolder($folder = 'uploads'): self
    {
        $this->folder = (is_production() ? '' : 'dev/') . $folder;
        return $this;
    }

    /**
     * 设置类型
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->folder = (is_production() ? '' : 'dev/') . $type;
        $extensions   = FileManager::kvExt($type);
        if ($extensions) {
            $this->setExtension($extensions);
        }
    }

    /**
     * 获取本地磁盘存储
     */
    public function storage(): FilesystemAdapter
    {
        static $disk;
        if (!$disk) {
            $disk = app('filesystem')->disk($this->disk);
        }
        return $disk;
    }

    /**
     * Set Extension
     * @param array $extension 支持的扩展
     */
    public function setExtension(array $extension = []): self
    {
        $this->allowedExtensions = $extension;
        return $this;
    }

    /**
     * District Size.
     * @param int $resize 设置resize 的区域
     */
    public function setResizeDistrict(int $resize): self
    {
        $this->resizeDistrict = $resize;
        return $this;
    }

    /**
     * 设置图片压缩质量
     * @param int $quality
     * @return self
     */
    public function setQuality(int $quality): self
    {
        $this->quality = $quality;
        return $this;
    }

    /**
     * 设置图片mime类型
     * @param $mime_type
     * @return DefaultFileProvider
     */
    public function setMimeType($mime_type): self
    {
        $this->mimeType = $mime_type;
        return $this;
    }

    /**
     * @inheritDoc
     * @throws ApplicationException
     */
    public function saveFile(UploadedFile $file): bool
    {
        if (!$file->isValid()) {
            return $this->setError($file->getErrorMessage());
        }

        // 存储, 根据后缀来进行区分
        if ($file->getClientOriginalExtension() && !in_array(strtolower($file->getClientOriginalExtension()), $this->allowedExtensions, true)) {
            return $this->setError('你只允许上传 "' . implode(',', $this->allowedExtensions) . '" 格式');
        }

        // 磁盘对象
        $Disk      = $this->storage();
        $extension = $file->getClientOriginalExtension();


        $fileRelativePath = $this->genRelativePath($extension);
        $zipContent       = file_get_contents($file->getPathname());

        /* 图片进行压缩, 其他不进行处理
         * ---------------------------------------- */
        if (in_array($extension, FileManager::kvExt(FileManager::TYPE_IMAGES), true)) {
            if (!$extension) {
                $extension = 'png';
            }
            // bmp 处理
            if ($file->getMimeType() === 'image/x-ms-bmp') {
                $img = imagecreatefrombmp($file->getRealPath());
                if ($img) {
                    ob_start();
                    imagepng($img);
                    $imgContent = ob_get_clean();
                    $zipContent = $imgContent;
                }
            }
            try {
                $zipContent = $this->resizeContent($extension, $zipContent);
            } catch (NotReadableException $e) {
                return $this->setError('图片源格式有误无法读取, 请转换图片格式再行上传');
            }
        }

        $Disk->put($fileRelativePath, $zipContent);

        $this->destination = $fileRelativePath;

        return true;
    }

    /**
     * @inheritDoc
     */
    public function resize($content, $width = 1920, $height = 1440, $crop = false): StreamInterface
    {
        if ($content instanceof Image) {
            $Image = $content;
        }
        else {
            $Image = $this->imageManager()->make($content);
        }

        if ($crop) {
            $widthCalc  = $Image->width() > $width ? $width : $Image->width();
            $heightCalc = $Image->height() > $height ? $height : $Image->height();
            $widthMax   = $Image->width() < $width ? $width : $Image->width();
            $heightMax  = $Image->height() < $height ? $height : $Image->height();

            // calc x, calc y
            $x = 0;
            $y = 0;
            if ($widthCalc >= $width) {
                $x = ceil(($widthMax - $widthCalc) / 2);
            }
            if ($heightCalc >= $height) {
                $y = ceil(($heightMax - $heightCalc) / 2);
            }
            if ($x || $y) {
                $Image->crop($width, $height, $x, $y);
            }
        }

        $Image->resize($width, $height, function (Constraint $constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });

        return $Image->stream('jpg', $this->quality);
    }

    /**
     * @inheritDoc
     * @throws ApplicationException
     */
    public function saveInput($content): bool
    {
        $extension = 'png';
        if (Str::contains($this->mimeType, '/')) {
            $extension = Str::after($this->mimeType, '/');
        }

        // 磁盘对象
        $Disk             = $this->storage();
        $fileRelativePath = $this->genRelativePath($extension);

        if (is_string($content) && UtilHelper::isUrl($content)) {
            $extension = FileHelper::ext($content);
            if (!$extension) {
                $extension = 'png';
            }
            $content = $this->imageManager()->make($content)->stream();
        }

        // 缩放图片
        if ($extension !== 'gif') {
            $zipContent = $this->resizeContent($extension, $content);
        }
        else {
            $zipContent = $content;
        }

        $Disk->put($fileRelativePath, $zipContent);
        $this->destination = $fileRelativePath;

        return true;
    }

    /**
     * 获取目标路径
     * @return string
     */
    public function getDestination(): string
    {
        return $this->destination;
    }

    /**
     * @param string $destination 设置目标地址
     */
    public function setDestination(string $destination): self
    {
        $this->destination = $destination;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getUrl(): string
    {
        // 磁盘 public_uploads 对应的是根目录下的 uploads, 所以这里的目录是指定的
        return $this->returnUrl . $this->destination;
    }

    /**
     * @inheritDoc
     */
    public function getReturnUrl(): string
    {
        return $this->returnUrl;
    }

    /**
     * 设置返回地址
     * @param string $url 地址
     */
    public function setReturnUrl(string $url): self
    {
        if (!Str::endsWith($url, '/')) {
            $url .= '/';
        }
        $this->returnUrl = $url;
        return $this;
    }

    /**
     * @param bool $isForceSetDestination
     * @return $this
     */
    public function setIsForceSetDestination(bool $isForceSetDestination): DefaultFileProvider
    {
        $this->isForceSetDestination = $isForceSetDestination;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function copyTo(string $dist): bool
    {
        // 强制删除
        if ($this->storage()->exists($dist)) {
            $this->storage()->delete($dist);
        }
        return $this->storage()->copy($this->destination, $dist);
    }

    /**
     * @inheritDoc
     */
    public function delete(): bool
    {
        if ($this->storage()->exists($this->destination)) {
            $this->storage()->delete($this->destination);
        }
        return true;
    }

    /**
     * @inerhitDoc
     */
    public function enableWatermark(): void
    {
        $this->watermark = true;
    }

    /**
     * @param string $extension 扩展名
     * @return string
     * @throws ApplicationException
     */
    private function genRelativePath(string $extension = 'png'): string
    {
        if ($this->isForceSetDestination && $this->destination) {
            $ext = FileHelper::ext($this->destination);
            if ($ext !== $extension) {
                throw new ApplicationException('指定文件的扩展类型不符, 可能导致图片无法展示');
            }
            return $this->destination;
        }
        $now      = Carbon::now();
        $fileName = $now->format('is') . Str::random(8) . '.' . $extension;

        return ($this->folder ? $this->folder . '/' : '') . $now->format('Ym/d/H/') . $fileName;
    }

    /**
     * 重设内容
     * @param string $extension  扩展
     * @param mixed  $img_stream 压缩内容
     * @return bool|StreamInterface
     */
    private function resizeContent(string $extension, $img_stream)
    {
        // 缩放图片
        if ($extension !== 'gif' && in_array($extension, FileManager::kvExt(FileManager::TYPE_IMAGES), true)) {
            $Image  = $this->imageManager()->make($img_stream);
            $width  = $Image->width();
            $height = $Image->height();

            $resize = FileManager::resizedSize($width, $height, $this->resizeDistrict, $this->resizeLongDistrict);
            if ($resize['resize']) {
                return $this->resize($Image, $resize['width'], $resize['height']);
            }
        }
        else {
            return $img_stream;
        }
        return $img_stream;
    }

    /**
     * @return ImageManager
     */
    private function imageManager(): ImageManager
    {
        return app('image');
    }
}
