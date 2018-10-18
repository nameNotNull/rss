<?php

namespace Business\Factory;

use Business\Impl\Rss\Zhihu;
use MobileApi\Util\Config;

class Rss
{
    private static $instance = [];

    /**
     * Method  create
     * Note
     *
     * @author wangxuan
     * @static
     *
     * @param $type
     *
     * @return HallMsg|SdkMsg|SdkPush|Sms|mixed|null
     */

    static function create($source)
    {
        $obj = null;
        if (isset(self::$instance[$source])) {
            $obj = self::$instance[$source];
        } else {
            $sources = Config::get('mapping.rss.source');
            switch ($source) {
                case ($sources['zhihu'] ?? ''):
                    $obj = new Zhihu();
                    break;
                case ($sources['laosiji'] ?? ''):
                    $obj = new Zhihu();
                    break;
                case ($sources['sina'] ?? ''):
                    $obj = new Zhihu();
                    break;
                default:
                    $obj = new Zhihu();
                    break;
            }
            self::$instance[$source] = $obj;
        }

        return $obj;
    }
}