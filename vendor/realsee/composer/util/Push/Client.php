<?php

namespace MobileApi\Util\Push;

use MobileApi\Util\Config;

/**
 * Client.php
 *
 * @author: Anson
 * @date  : 2016-09-21 13:00
 */
class Client
{
    public function send(Message $message)
    {
        static $socket;

        if (!$socket || !$this->checkSocketWritable($socket)) {
            $socket = $this->getConnectedSocket();
        }

        $packstr = msgpack_pack($message->toArray());

        $i = 0;
        WRITE_START:
        $ret = socket_write($socket, $packstr, strlen($packstr));

        if ($ret) {
            $pack = socket_read($socket, 1024);

            if ($pack) {
                return msgpack_unpack($pack);
            }
        }

        if ($i++ < 3) {
            $socket = $this->getConnectedSocket();
            goto WRITE_START;
        }

        throw new \Exception('socket set option error. ' . socket_strerror(socket_last_error()));
    }

    private function getConnectedSocket()
    {
        $i = 0;
        START_CONNECT:
        try {
            $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            if (!$socket) {
                throw new \Exception('socket create error.');
            }
            $ret = socket_set_option($socket, SOL_SOCKET, SO_KEEPALIVE, 1);
            if (!$ret) {
                throw new \Exception('socket set option error. ' . socket_strerror(socket_last_error()));
            }
            $conn = @socket_connect($socket, $this->getHost(), $this->getPort());
            if (!$conn) {
                throw new \Exception('socket connect error. ' . socket_strerror(socket_last_error()));
            }
        } catch (\Exception $e) {
            if ($i++ < 3) {
                goto START_CONNECT;
            }

            throw $e;
        }

        return $socket;
    }

    private function checkSocketWritable($socket)
    {
        $w = [$socket];

        return socket_select($null, $w, $null, $null) ? true : false;
    }

    public static function getInstance()
    {
        static $instance;

        if ($instance) {
            return $instance;
        }
        $instance = new static();

        return $instance;
    }

    private function getHost()
    {
        static $ips;
        static $last_update_at;

        if ($last_update_at && $last_update_at < time() - 60 * 5) {
            $ips = null;
        }

        if (empty($ips)) {
            $host = Config::get('application.push.host', '');
            $ips  = gethostbynamel($host);

            $last_update_at = time();
        }

        return array_pop($ips);
    }

    private function getPort()
    {
        return Config::get('application.push.port', '');
    }

}