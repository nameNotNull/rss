<?php

namespace MobileApi\Exception\Http;

class HttpBaseException extends \Exception
{

    protected $responseData = null;

    public function __construct($message = '', $code = 0, $responseData = null)
    {
        parent::__construct($message, (int)$code);

        $this->responseData = $responseData;
    }

    public function getResponseData()
    {
        return $this->responseData;
    }

}
