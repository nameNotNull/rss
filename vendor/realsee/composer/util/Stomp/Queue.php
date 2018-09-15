<?php

namespace MobileApi\Util\Stomp;

use MobileApi\Util\Stomp\Client as StompClient;

class Queue
{
    protected $channel;

    protected $client;

    /**
     * @return StompClient
     */
    public function getClient()
    {
        if (isset($this->client) && $this->client instanceof StompClient) {
            return $this->client;
        }

        $this->client = StompClient::getInstance($this->channel);

        return $this->client;
    }

    public function __construct($channel)
    {
        if (is_string($channel)) {
            $this->channel = $channel;
        } else {
            if ($channel instanceof StompClient) {
                $this->client = $channel;
                $this->channel = $channel->getChannel();
            } else {
                throw new \Exception('Util/Queue channel Error.');
            }
        }

    }

    public function getChannel()
    {
        return $this->channel;
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array([
            $this->getClient(),
            $name,
        ], $arguments);
    }

    public function send($msg)
    {
        return call_user_func_array([
            $this->getClient(),
            'send',
        ], [$msg]);
    }
}
