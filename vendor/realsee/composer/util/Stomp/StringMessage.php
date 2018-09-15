<?php

namespace MobileApi\Util\Stomp;

class StringMessage extends AbstractMessage
{

    public function getBody()
    {
        return $this->body;
    }

    public function setBody($body)
    {
        $this->body = (string)$body;

        return $this;
    }

    public function __toString()
    {
        return (string)$this->getBody();
    }

    public function getMessageId()
    {
        if (isset($this->headers["message-id"])) {
            return $this->headers["message-id"];
        } else {
            return null;
        }
    }
}
