<?php

namespace MobileApi\Util\Stomp;

abstract class AbstractMessage extends Frame
{
    public function __construct()
    {
        $args = func_get_args();

        if (count($args) == 0) {
            parent::__construct('', [], '');
        } elseif (count($args) == 1) {
            parent::__construct('SEND', [], '');
            $this->setBody($args[0]);
        } elseif (count($args) == 2) {
            parent::__construct('SEND', $args[1], $args[0]);
            $this->setBody($args[0]);
        } else {
            parent::__construct($args[0], $args[1], $args[2]);
            $this->setBody($args[2]);
        }

    }

    public function __toString()
    {
        return (string)$this->body;
    }

    public function getMessageId()
    {
        if (isset($this->headers["message-id"])) {
            return $this->headers["message-id"];
        } else {
            return null;
        }
    }

    abstract public function getBody();

    abstract public function setBody($body);

}
