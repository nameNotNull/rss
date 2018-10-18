<?php
namespace Business\Impl\Rss;

use MobileApi\Util\Config;
use Utils\Rss;

class Zhihu extends \Business\Base\Rss {

    protected function _load($type)
    {
        $types  = Config::get('mapping.rss.zhihu.type');

        $url = Config::get('mapping.rss.zhihu.type.'.$type.'.url');

        $result = [];
        if($type == $types['daily']){
        }else{
            $rss = Rss::loadRss($url);
            $list = $rss->toArray();
            $result = array_slice($list['item'],0,5);

        }

        return $result;
    }

    protected function rule()
    {

    }
}