<?php

namespace MobileApi\Util;

/**
 * Interface RequestInterface
 *
 * Request 类接口，想使用Log类必须Request必须实现
 *
 * @package MobileApi\Util
 */
interface RequestInterface
{

    public static function getPlatform();

    public static function getChannel();

    public static function getVersion();

    public static function getAccessToken();

    public static function getRealUserId();

    public static function getUserId();

    public static function getUserInfo();

}
