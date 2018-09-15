<?php

namespace MobileApi\Util;

use Yaf\Exception;

class Message
{

    const DEFAULT_MESSAGE = 'system_error';

    const LEFT_FLAG = '{{ ';

    const RIGHT_FLAG = ' }}';

    /**
     * Variable  messages
     *
     * @author   wangxuan
     * @static
     * @var      array
     */
    private static $_messages = [];

    /**
     * Method __construct
     *
     * @author wangxuan
     */
    private function __construct()
    {

    }

    /**
     * Method  get
     *
     * @author wangxuan
     * @static
     *
     * @param string $message
     * @param array  $replaces
     *
     * @return mixed
     */
    public static function get($message, $replaces = null)
    {
        if (empty($message)) {
            $message = self::DEFAULT_MESSAGE;
        }

        if (!isset(self::$_messages[$message])) {
            $messageInfo = Config::get("message.{$message}", Config::get('message.' . self::DEFAULT_MESSAGE));

            self::$_messages[$message] = $messageInfo;
        } else {
            $messageInfo = self::$_messages[$message];
        }

        if (empty($messageInfo)) {
            return $message;
        }

        if (is_numeric($messageInfo['code'])) {
            $messageInfo['code'] = (int)$messageInfo['code'];
        }

        if (!empty($replaces) && is_array($replaces)) {
            $search = array_keys($replaces);

            array_walk($search, function (&$value, $key) {
                $value = self::LEFT_FLAG . $value . self::RIGHT_FLAG;
            });

            $replace = array_values($replaces);

            $messageInfo['text'] = str_replace($search, $replace, $messageInfo['text']);
        }

        return $messageInfo;
    }

    /**
     * Method  getCode
     *
     * @author wangxuan
     * @static
     *
     * @param string $message
     *
     * @return int|null
     */
    public static function getCode($message)
    {
        $messageInfo = self::get($message);

        return isset($messageInfo['code']) ? $messageInfo['code'] : null;
    }

    /**
     * Method  getText
     *
     * @author wangxuan
     * @static
     *
     * @param string $message
     * @param array  $replaces
     *
     * @return null|string
     */
    public static function getText($message, $replaces = null)
    {
        $messageInfo = self::get($message, $replaces);

        return isset($messageInfo['text']) ? $messageInfo['text'] : null;
    }

}
