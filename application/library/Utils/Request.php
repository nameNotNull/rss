<?php

namespace Utils;
use MobileApi\Util\Server;
use MobileApi\Util\RequestInterface;
use Yaf\Registry;

class Request implements RequestInterface
{
    protected static $controller = null;

    protected static $user = null;

    private function __construct()
    {
    }

    public static function init()
    {
        // self::authValidation();
        // self::getUser();
    }


    public static function setController($controller)
    {
        self::$controller = $controller;
    }


    public static function getController()
    {
        return self::$controller;
    }

    public static function getWorkId()
    {
        $workId = self::getController()->getParam('work_id');

        $workCode = self::getController()->getParam('work_code');

        if (empty($workId) && !empty($workCode)) {
            return Convert::userCodeToId($workCode);
        } else {
            return (int)$workId;
        }
    }

    public static function getRealUserId()
    {
        $userId = self::getUserId();

        if (!empty($userId)) {
            return $userId;
        } else {
            return (int)self::getController()->getRequest()->get('user_id', 0);
        }
    }

    public static function getUserId()
    {
        try {
            return (int)self::getController()->getParam('user_id');
        } catch (\Throwable $exception) {
            return 0;
        }
    }

    public static function getUserInfo()
    {

    }

    public static function getUri()
    {
        return Registry::get('request_uri');
    }

    public static function getUserAgent()
    {
        return Server::get('http_user_agent');
    }

    public static function getAccessToken()
    {

    }

    public static function getChannel()
    {
    }

    public static function getPlatform()
    {
        $ua = self::getUserAgent();

        if (stripos($ua, 'ios') !== false) {
            $platform = 'ios';
        } elseif (stripos($ua, 'android') !== false) {
            $platform = 'android';
        } else {
            $platform = 'api';
        }

        return $platform;
    }

    public static function getDeviceId()
    {
    }

    public static function getSsid()
    {
    }

    public static function getUdid()
    {
    }

    public static function getUuid()
    {
    }

    public static function getTdid()
    {

    }

    public static function getVersion()
    {
    }

    public static function getVersionString()
    {

    }

    public static function getImVersionString()
    {
    }

    public static function getUser()
    {

    }

    public static function isIOS()
    {
        return self::getPlatform() === 'ios';
    }

    public static function isAndroid()
    {
        return self::getPlatform() === 'android';
    }


    public static function authValidation()
    {

    }
}
