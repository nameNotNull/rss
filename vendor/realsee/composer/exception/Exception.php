<?php

namespace MobileApi\Exception;

use MobileApi\Util\Message;

class Exception extends \Exception
{

    /**
     * Variable  rawMessage
     *
     * @author   wangxuan
     * @var      string
     */
    private $rawMessage = '';

    /**
     * Method __construct
     *
     * @author wangxuan
     *
     * @param string $message
     * @param array  $replaces
     */

    public function __construct($message, $replaces = [])
    {
        $this->rawMessage = $message;

        parent::__construct(Message::getText($message, $replaces), Message::getCode($message));
    }

    /**
     * Method  getRawMessage
     *
     * @author wangxuan
     * @return string
     */
    public function getRawMessage()
    {
        return $this->rawMessage;
    }

}
