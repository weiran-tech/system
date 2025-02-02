<?php

declare(strict_types = 1);

namespace Weiran\System\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * 系统维护
 */
class MaintainMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @var string 标题
     */
    public string $title;

    /**
     * @var string 内容
     */
    public string $content;

    /**
     * @var string 附加的文件
     */
    private string $file;

    /**
     * Create a new message instance.
     *
     * @param string $title
     * @param string $content
     * @param string $file
     */
    public function __construct(string $title = '', string $content = '', string $file = '')
    {
        $this->title   = $title;
        $this->subject = $title;
        $this->content = $content;
        $this->file    = $file;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): self
    {
        $view = $this->view('py-system::mail.maintain');
        if ($this->file) {
            $view->attach($this->file);
        }

        return $view;
    }
}
