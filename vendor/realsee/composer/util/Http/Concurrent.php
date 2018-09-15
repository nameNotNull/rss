<?php
/**
 * Concurrent.php
 *
 * @author: Anson
 * @date  : 2017-02-09 16:36
 */

namespace MobileApi\Util\Http;

use MobileApi\Exception\Http\HttpBaseException;
use Psr\Http\Message\ResponseInterface;

class Concurrent
{
    protected $handlers = [];

    public function __construct(...$handlers)
    {
        if (empty($handlers)) {
            return;
        }

        //兼容第一个参数是数组的情况
        if (!empty($handlers[0]) && is_array($handlers[0])) {
            $handlers = $handlers[0];
        }

        foreach ($handlers as $handler) {
            if ($handler instanceof AsyncHandler) {
                call_user_func([
                    $this,
                    'add',
                ], $handler);
            }

        }
    }

    public function add(AsyncHandler $handler)
    {
        $this->handlers[] = $handler;
    }

    public function send()
    {
        $promises = [];

        foreach ($this->handlers as $index => $handler) {
            $promises[] = $handler->getPromise()->then(function (ResponseInterface $response) use ($handler) {
                $handler->setResponse($response);
            });
        }

        // \GuzzleHttp\Promise\unwrap($promises);

        $results = \GuzzleHttp\Promise\settle($promises)->wait();

        if (empty($results)) {
            return $this;
        }

        foreach ($results as $index => $result) {
            if ($result['state'] === 'rejected' && $result['reason'] instanceof \Exception && !($result['reason'] instanceof HttpBaseException)) {
                $this->handlers[$index]->setResponse(null, $result['reason']);
            }
        }

        return $this;
    }

    public function getAllResult()
    {
        $result = [];

        foreach ($this->handlers as $handler) {
            $result[] = $handler->getResult();
        }

        return $result;
    }

}