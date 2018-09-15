<?php

namespace MobileApi\Util;

use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\BufferHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Yaf\Registry;

/**
 * Class     Log
 *
 * @package  MobileApi\Util
 * @author   wangxuan
 * @method static apiInfo($message, array $context = [], $channel = null)
 * @method static apiError($message, array $context = [], $channel = null)
 * @method static errorInfo($message, array $context = [], $channel = null)
 */
class Log
{

    private $_logger = null;

    private $_bufferHandler = null;

    private $_enable = false;

    private $_bufferEnabled = true;

    private static $_requestHandler = null;

    private static $_channel = 'trace';

    private static $_instances = [];

    private static $_sequence = 0;

    private static $_inputExtra = [];

    private static $_outputExtra = [];

    /**
     * Method  __construct
     *
     * @author wangxuan
     *
     * @param string $channel
     */
    public function __construct($channel)
    {
        $logConfig = Config::get('application.log');

        if (empty($logConfig['enable'])) {
            return;
        }

        if (self::$_requestHandler === null) {
            self::$_requestHandler = Request::class;
        }

        $this->_enable = true;

        $this->_bufferEnabled = Registry::get('log_use_buffer') !== false;

        if (isset($logConfig['file'][$channel])) {
            $this->_logger = new Logger($channel);

            $path = str_replace('{%date%}', date('Y-m-d'), $logConfig['file'][$channel]);

            $streamHandler = new StreamHandler($path, (Env::isProduct() || Env::isTest()) ? Logger::INFO : Logger::DEBUG);
            $streamHandler->setFormatter(new JsonFormatter($logConfig['format']));
            if ($this->_bufferEnabled) {
                $this->_bufferHandler = new BufferHandler($streamHandler);

                $this->_bufferHandler->setFormatter(new JsonFormatter($logConfig['format']));

                $this->_logger->pushHandler($this->_bufferHandler);
            } else {
                $this->_logger->pushHandler($streamHandler);
            }

            $this->_logger->pushProcessor(function ($record) use ($logConfig, $channel) {
                try {
                    $accessToken = self::$_requestHandler::getAccessToken();
                } catch (\Exception $exception) {
                    $accessToken = '';
                }

                try {
                    $platform = self::$_requestHandler::getPlatform();
                    $appName  = self::$_requestHandler::getChannel();
                    $version  = self::$_requestHandler::getVersion();
                } catch (\Exception $exception) {
                    $platform = '';
                    $appName  = '';
                    $version  = '';
                }

                $record['index_day']  = date('Y-m-d');
                $record['request_id'] = Registry::get('request_id');
                $record['platform']   = $platform;
                $record['channel']    = $channel;
                $record['app_name']   = $appName;
                $record['version']    = $version;
                $record['cost']       = 0;
                $record['api_url']    = '';
                $record['uri']        = Registry::get('request_uri');

                if (isset($logConfig['max_length'])) {
                    if ($record['channel'] === 'api' && strlen(json_encode($record)) > $logConfig['max_length']) {
                        $responseStr                   = json_encode($record['context']['response']);
                        $defaultLength                 = ceil(strlen($responseStr) / 7);
                        $record['context']['response'] = ['abstract' => substr($responseStr, 0, $logConfig['intercept_length_start'] ?? $defaultLength) . '...' . substr($responseStr, -($logConfig['intercept_length_end'] ?? $defaultLength))];
                    } elseif ($record['channel'] === 'request' && $record['message'] === 'output' && strlen(json_encode($record)) > $logConfig['max_length']) {
                        $dataStr                   = isset($record['context']['data']) ? json_encode($record['context']['data']) : json_encode($record['context']);
                        $defaultLength             = ceil(strlen($dataStr) / 7);
                        $record['context']['data'] = ['abstract' => substr($dataStr, 0, $logConfig['intercept_length_start'] ?? $defaultLength) . '...' . substr($dataStr, -($logConfig['intercept_length_end'] ?? $defaultLength))];
                    }
                }

                if ($record['message'] === 'output' && !empty(self::$_outputExtra)) {
                    if (isset($record['context']['data']) && is_array($record['context']['data'])) {
                        $record['context']['data']['_extra'] = self::$_outputExtra;
                    } else {
                        $record['context']['data'] = ['_extra' => self::$_outputExtra];
                    }
                }

                if ($record['message'] === 'input' && !empty(self::$_inputExtra)) {
                    if (!is_array($record['context'])) {
                        $record['context'] = [
                            '_extra' => self::$_inputExtra,
                        ];
                    } else {
                        $record['context']['_extra'] = self::$_inputExtra;
                    }
                }

                if ($record['channel'] === 'api') {
                    $record['cost']    = $record['context']['total_time'];
                    $record['api_url'] = $record['context']['url'];
                }

                if ($record['channel'] === 'request' && $record['message'] === 'output') {
                    $record['cost'] = $record['context']['cost'] ?? 0;
                }

                $record['context'] = json_encode($record['context'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                $record['user_id']   = self::$_requestHandler::getRealUserId() ?? 0;
                $record['client_ip'] = Network::getClientIp();
                $record['extra']     = [
                    'access_token' => $accessToken,
                    'device_id'    => Server::get('http_lianjia_device_id', ''),
                    'uuid'         => Cookie::get('lianjia_uuid', ''),
                    'ssid'         => Cookie::get('lianjia_ssid', ''),
                    'udid'         => Cookie::get('lianjia_udid', ''),
                    'sequence'     => ++self::$_sequence,
                ];

                unset($record['datetime']);

                return array_merge(['request_time' => date('Y-m-d H:i:s')], $record);
            });
        }
    }

    /**
     * 设置input日志补充字段
     *
     * @param $extra
     */
    public static function setInputExtra($extra)
    {
        self::$_inputExtra = array_merge($extra, self::$_inputExtra);
    }

    /**
     * 设置output日志补充字段
     *
     * @param $extra
     */
    public static function setOutputExtra($extra)
    {
        self::$_outputExtra = array_merge($extra, self::$_outputExtra);
    }


    /**
     * Method  __destruct
     *
     * @author wangxuan
     */
    public function __destruct()
    {
        if ($this->_enable === true && $this->_bufferEnabled) {
            $this->_bufferHandler->close();
        }
    }

    /**
     * Method  getInstance
     *
     * @author wangxuan
     * @static
     *
     * @param string $channel
     *
     * @return mixed
     */
    public static function getInstance($channel = 'request')
    {
        $key = $channel . '-' . date('Ymd');

        if (!isset(self::$_instances[$key])) {
            self::$_instances[$key] = new self($channel);
        }

        return self::$_instances[$key];
    }

    /**
     * 设置request类
     * @param $className
     *
     * @throws \Exception
     */
    public static function setRequestHandler($className)
    {
        $implements = class_implements($className);
        if (!isset($implements[RequestInterface::class])) {
            throw new \Exception('Request be must implemented RequestInterface');
        }
        self::$_requestHandler = $className;
    }


    /**
     * Method  debug
     *
     * @author wangxuan
     * @static
     *
     * @param string $message
     * @param array  $context
     * @param string $channel
     *
     * @return mixed
     */
    public static function debug($message, array $context = [], $channel = null)
    {
        return static::_record($channel, 'debug', $message, $context);
    }


    /**
     * Method  info
     *
     * @author wangxuan
     * @static
     *
     * @param string $message
     * @param array  $context
     * @param string $channel
     *
     * @return mixed
     */
    public static function info($message, array $context = [], $channel = null)
    {
        return static::_record($channel, 'info', $message, $context);
    }

    /**
     * Method  notice
     *
     * @author wangxuan
     * @static
     *
     * @param string $message
     * @param array  $context
     * @param string $channel
     *
     * @return mixed
     */
    public static function notice($message, array $context = [], $channel = null)
    {
        return static::_record($channel, 'notice', $message, $context);
    }

    /**
     * Method  warning
     *
     * @author wangxuan
     * @static
     *
     * @param string $message
     * @param array  $context
     * @param string $channel
     *
     * @return mixed
     */
    public static function warning($message, array $context = [], $channel = null)
    {
        return static::_record($channel, 'warning', $message, $context);
    }

    /**
     * Method  error
     *
     * @author wangxuan
     * @static
     *
     * @param string $message
     * @param array  $context
     * @param string $channel
     *
     * @return mixed
     */
    public static function error($message, array $context = [], $channel = null)
    {
        return static::_record($channel, 'error', $message, $context);
    }

    /**
     * Method  critical
     *
     * @author wangxuan
     * @static
     *
     * @param string $message
     * @param array  $context
     * @param string $channel
     *
     * @return mixed
     */
    public static function critical($message, array $context = [], $channel = null)
    {
        return static::_record($channel, 'critical', $message, $context);
    }

    /**
     * Method  alert
     *
     * @author wangxuan
     * @static
     *
     * @param string $message
     * @param array  $context
     * @param string $channel
     *
     * @return mixed
     */
    public static function alert($message, array $context = [], $channel = null)
    {
        return static::_record($channel, 'alert', $message, $context);
    }

    /**
     * Method  emergency
     *
     * @author wangxuan
     * @static
     *
     * @param string $message
     * @param array  $context
     * @param string $channel
     *
     * @return mixed
     */
    public static function emergency($message, array $context = [], $channel = null)
    {
        return static::_record($channel, 'emergency', $message, $context);
    }


    /**
     * Method  _record
     *
     * @author wangxuan
     * @static
     *
     * @param string $channel
     * @param string $level
     * @param string $message
     * @param array  $context
     *
     * @return bool
     */
    private static function _record($channel, $level, $message, array $context = [])
    {
        if ($channel === null) {
            $channel = static::$_channel;
        } else {
            $channel = strtolower($channel);
        }

        $instance = static::getInstance($channel);

        if (!empty($instance) && $instance->_enable === true) {
            return $instance->_logger->$level($message, $context);
        }

        return false;
    }

    /**
     * Method  __call
     *
     * @author wangxuan
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if ($this->_enable !== true) {
            return false;
        }

        return call_user_func_array([
            $this->_logger,
            $name,
        ], $arguments);
    }

    /**
     * Method  __callStatic
     *
     * @author wangxuan
     * @static
     *
     * @param $name
     * @param $arguments
     *
     * @return bool|mixed
     */
    public static function __callStatic($name, $arguments)
    {
        $names = explode('_', Convert::camelToUnderline($name));

        if (!isset($names[1])) {
            return false;
        }

        $channel    = $names[0];
        $methodName = $names[1];

        if (static::getInstance($channel)->_enable === false || method_exists(static::getInstance($channel)->_logger, $methodName) === false) {
            return false;
        }

        return call_user_func_array([
            static::getInstance($channel)->_logger,
            $methodName,
        ], $arguments);
    }

}