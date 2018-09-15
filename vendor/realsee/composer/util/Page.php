<?php

namespace MobileApi\Util;

use Enums\Client as ClientEnums;
use Yaf\Config\Ini;

class Page
{

    /**
     * Method __construct
     *
     * @author wangxuan
     */
    private function __construct()
    {

    }

    /**
     * Method  offsetToPageNumber
     *
     * @author wangxuan
     * @static
     *
     * @param int $offset
     * @param int $limit
     *
     * @return int
     */
    public static function offsetToPageNumber($offset = ClientEnums::DEFAULT_OFFSET, $limit = ClientEnums::DEFAULT_LIMIT)
    {
        if ((int)$limit === 0) {
            return 0;
        }

        $offset += 1;

        if ($offset < 1 || $offset <= $limit) {
            return 1;
        }

        return (int)ceil($offset / $limit);
    }

}
