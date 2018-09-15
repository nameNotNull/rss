<?php

namespace MobileApi\Util\Http;

use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use MobileApi\Exception\Http\HttpResponseException;
use Psr\Http\Message\ResponseInterface;

class AsyncHandler
{
    protected $promise;

    protected $response;

    protected $result;

    protected $responseCallback;

    protected $onFailed;

    public function __construct(PromiseInterface $promise, $responseCallback = null)
    {
        $this->promise          = $promise;
        $this->responseCallback = $responseCallback ?? function (ResponseInterface $response) {
                return (string)$response->getBody();
            };
    }

    public function getPromise()
    {
        return $this->promise;
    }

    public function setResponse(ResponseInterface $response = null, \Exception $exception = null)
    {
        $this->response = $response;

        try {
            $this->result = ($this->responseCallback)($response, $exception);
        } catch (HttpResponseException $e) {
            $this->handleFailed($e);
        }

        return $this;
    }

    protected function getResponse()
    {
        return $this->response;
    }

    public function getResult()
    {
        return $this->result;
    }

    public function onFailed($callable)
    {
        $this->onFailed = $callable;

        return $this;
    }

    public function ignoreFail()
    {
        return $this->onFailed(function ($e) {
        });
    }

    protected function handleFailed(HttpResponseException $exception)
    {
        if (is_callable($this->onFailed)) {
            return call_user_func($this->onFailed, $exception);
        }

        throw $exception;
    }

}