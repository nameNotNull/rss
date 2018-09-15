<?php

namespace MobileApi\Util\Stomp;

class Frame extends \StompFrame
{
    public $command;

    public $body;

    public $headers;

    public function __construct($command = '', $headers = [], $body = '')
    {
        $this->command = $command;
        $this->headers = $headers;
        $this->body    = $body;
    }

}
