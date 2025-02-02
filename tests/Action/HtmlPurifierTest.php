<?php

declare(strict_types = 1);

namespace Weiran\System\Tests\Action;

use File;
use HTMLPurifier;
use HTMLPurifier_Config;
use Weiran\Framework\Application\TestCase;
use Storage;

class HtmlPurifierTest extends TestCase
{
    /**
     * html净化
     * @return void
     */
    public function testPurifier(): void
    {
        $old       = '
        <H1>我是一级标题</H1>
        <h2>我是二级标题</h2>
        <h3 style="color: red">我是红色的三级标题</h3>
        <iframe src="http://baidu.com"></iframe>
        <script>alert("alert")</script>
        <sCRiPt sRC=http://baidu.com></sCrIpT>
        <style>h1{color: red}</style>
        ';
        $Storage   = Storage::disk('storage');
        $cachePath = $Storage->path('html_purifier/');
        if (!File::exists($cachePath)) {
            File::makeDirectory($cachePath);
        }
        $config = HTMLPurifier_Config::createDefault();
        $config->set('Cache.SerializerPath', $cachePath);
        $Purifier = new HTMLPurifier($config);
        $new      = $Purifier->purify($old);

        $this->assertFalse(strpos($new, "<script"));
        $this->assertFalse(strpos($new, "<sCRiPt"));
        $this->assertFalse(strpos($new, "<style"));
        $this->assertFalse(strpos($new, "<iframe"));
    }
}