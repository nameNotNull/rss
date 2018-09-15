<?php

namespace MobileApi\Util\Stomp;

class JsonMessage extends AbstractMessage
{

    public function getBody()
    {
        return json_decode($this->body, true);
    }

    public function setBody($body)
    {
        if (is_string($body)) {
            return $this;
        }

        $this->body = json_encode($body);

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
