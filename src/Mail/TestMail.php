<?php

declare(strict_types = 1);

namespace Weiran\System\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * 测试发送
 */
class TestMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * 发送内容
     */
    public string $content;

    /**
     * Create a new message instance.
     */
    public function __construct(string $content = '')
    {
        $this->content = $content;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('weiran-system::mail.test');
    }
}
