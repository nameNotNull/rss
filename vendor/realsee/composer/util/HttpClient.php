<?php

namespace MobileApi\Util;

use Curl\Curl;
use Yaf\Registry;

class HttpClient
{

    public static $connect_timeout = 1;

    public static $request_timeout = 3;

    public static $request_retry_count = 3;

    /**
     * Method __construct
     *
     * @author wangxuan
     */
    private function __construct()
    {

    }

    /**
     * Method  getInstance
     *
     * @author wangxuan
     * @static
     *
     * @param int $connectTimeout
     * @param int $requestTimeout
     *
     * @return Curl
     */
    public static function getInstance($connectTimeout = null, $requestTimeout = null)
    {
        $curl = new Curl();

        $curl->setJsonDecoder(function ($response) {
            $data = json_decode($response, true);

            if (JSON_ERROR_NONE === json_last_error()) {
                return $data;
            }

            return $response;
        });

        if ($connectTimeout === null) {
            $connectTimeout = self::$connect_timeout;
        }

        if ($requestTimeout === null) {
            $requestTimeout = self::$request_timeout;
        }

        $curl->setConnectTimeout((int)$connectTimeout);

        $curl->setTimeout((int)$requestTimeout);

        return $curl;
    }

}
