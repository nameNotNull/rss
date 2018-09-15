<?php

namespace MobileApi\Util\Stomp;

use MobileApi\Util\Config;
use MobileApi\Util\Log;
use Yaf\Registry;

class Client
{
    protected $channel;

    protected $stomp;

    protected $stomp_channel;

    protected static $instance;

    const STRING_FRAME = StringMessage::class;
    const JSON_FRAME   = JsonMessage::class;

    protected function __construct($channel)
    {
        $this->channel = $channel;
    }

    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @param $channel
     *
     * @return static
     */
    public static function getInstance($channel)
    {
        if (!isset(static::$instance[$channel]) || false == (static::$instance[$channel] instanceof self)) {
            static::$instance[$channel] = new static($channel);
        }

        return static::$instance[$channel];
    }

    public static function clear()
    {
        static::$instance = [];
    }

    public function __call($name, $arguments)
    {
        return $this->call([
            $this->getStomp(),
            $name,
        ], $arguments);
    }

    /**
     * 方法调用(封装异常)
     *
     * @param $call
     * @param $arguments
     *
     * @return bool
     * @throws RuntimeException
     */
    protected function call($call, $arguments)
    {
        try {
            $ret = call_user_func_array($call, $arguments);

            Log::info("StompClient-call: func:" . json_encode($call) . "; args:" . json_encode($arguments) . '; ret:' . json_encode($ret) . ';');

            return $ret;
        } catch (\StompException $e) {
            throw new RuntimeException($e->getMessage(), $e->getCode());
        } catch (ChannelDisabledException $e) {
            Log::warning("Caught a StompChannelDisabledException, channel: {$this->channel}");
        }

        return false;
    }

    /**
     * 获取当前channel下的单例Stomp实体
     *
     * @return \Stomp
     * @throws ChannelDisabledException
     * @throws RuntimeException
     */
    public function getStomp()
    {
        if ($this->stomp instanceof \Stomp && $this->stomp->error()) {
            Log::warning("Caught a stomp error:" . $this->stomp->error());
            $this->stomp = null;
        }

        return $this->init()->stomp;
    }

    private function init()
    {
        if (empty($this->stomp) || !($this->stomp instanceof \Stomp)) {
            $conf = $this->getConf();
            if (!$conf['enable']) {
                throw new ChannelDisabledException('channel ' . $this->channel . ' disabled.');
            }
            $this->stomp_channel = $conf['channel'];
            foreach ($conf['address'] as $conn) {
                try {
                    $this->stomp = new \Stomp($conn, $conf['user'], $conf['password']);
                    $this->stomp->setReadTimeout($conf['timeout']);
                    break;
                } catch (\StompException $e) {
                    Log::warning("Caught a StompException:" . $e->getMessage());
                    $this->stomp = null;
                }
            }
            if (empty($this->stomp)) {
                Log::error("activemq connect failed");
                throw new RuntimeException("Connect activemq failed");
            }
        }

        return $this;
    }

    /**
     * 获取当前channel下的连接配置
     *
     * @return mixed
     * @throws RuntimeException
     */
    public function getConf()
    {
        $env    = \Yaf\Application::app()->environ();
        $conf   = Config::get("queue.{$env}.stomp.{$this->channel}", []);
        $common = Config::get("queue.{$env}.stomp.common", []);

        if (!$conf) {
            throw new RuntimeException('Can not find channel: ' . $this->channel . '.');
        }
        $address = isset($conf['address']) ? $conf['address'] : $common['address'];

        $conf['address']  = $address ? explode(',', $address) : [];
        $conf['user']     = isset($conf['user']) ? $conf['user'] : $common['user'];
        $conf['password'] = isset($conf['password']) ? $conf['password'] : $common['password'];

        return $conf;
    }

    /**
     * 根据msg类型适配容器
     *
     * @param $msg
     *
     * @return JsonMessage|StringMessage
     * @throws RuntimeException
     */
    protected function adaptMsgObj($msg)
    {
        if (is_string($msg) || is_numeric($msg)) {
            return new StringMessage($msg);
        } else {
            if (is_array($msg)) {
                return new JsonMessage($msg);
            } else {
                if (is_object($msg)) {
                    return $msg;
                }
            }
        }

        throw new RuntimeException('Something wrong with msg body.');
    }

    /**
     * 发送
     *
     * @param string|number|array|AbstractMessage $msg
     * @param null                                $transaction_id
     *
     * @return bool
     * @throws ChannelDisabledException
     * @throws RuntimeException
     */
    public function send($msg, $transaction_id = null)
    {
        $msg     = $this->adaptMsgObj($msg);
        $headers = [];
        if ($transaction_id) {
            $headers['transaction'] = $transaction_id;
        }

        return $this->call([
            $this->getStomp(),
            'send',
        ], [
            $this->stomp_channel,
            $msg,
            $headers,
        ]);
    }

    /**
     * 读取一个frame
     *
     * @param $frame 容器类
     *
     * @return AbstractMessage
     * @throws ChannelDisabledException
     * @throws RuntimeException
     */
    public function read($frame = self::STRING_FRAME)
    {
        $frameObj = $this->call([
            $this->getStomp(),
            'readFrame',
        ], [$frame]);
        if (is_object($frameObj) && property_exists($frameObj, 'headers') && array_key_exists('id', $frameObj->headers)) {
            unset($frameObj->headers['id']);
        }

        return $frameObj;
    }

    public function subscribe()
    {
        return $this->call([
            $this->getStomp(),
            'subscribe',
        ], [$this->stomp_channel]);
    }

    public function unsubscribe()
    {
        return $this->call([
            $this->getStomp(),
            'unsubscribe',
        ], [$this->stomp_channel]);
    }

    public function has()
    {
        return $this->call([
            $this->getStomp(),
            'hasFrame',
        ], []);
    }

    public function begin($transaction_id)
    {
        return $this->call([
            $this->getStomp(),
            'begin',
        ], [$transaction_id]);
    }

    public function abort($transaction_id)
    {
        return $this->call([
            $this->getStomp(),
            'abort',
        ], [$transaction_id]);
    }

    public function commit($transaction_id)
    {
        return $this->call([
            $this->getStomp(),
            'commit',
        ], [$transaction_id]);
    }

    public function ack($msg)
    {
        return $this->call([
            $this->getStomp(),
            'ack',
        ], [$msg]);
    }

    public function setReadTimeout($seconds)
    {
        return $this->call([
            $this->getStomp(),
            'setReadTimeout',
        ], [(int)$seconds]);
    }

    public function getReadTimeout()
    {
        return $this->call([
            $this->getStomp(),
            'getReadTimeout',
        ], []);
    }

    public function getSessionId()
    {
        return $this->call([
            $this->getStomp(),
            'getSessionId',
        ], []);
    }

    public function __destruct()
    {
        unset($this->stomp);
    }
}
