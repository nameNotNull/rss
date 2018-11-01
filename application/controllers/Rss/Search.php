<?php

use MobileApi\Util\Config;
use MobileApi\Util\Cache;
use Enums\Cache as CacheEnums;

class Rss_Search_Controller extends Base_Controller
{

    public function process()
    {
        $source = $this->getParam('source', 0);
        $type   = $this->getParam('type', 0);
        $page   = $this->getParam('page', 1);
        $size   = $this->getParam('size', 20);

        $cache = new Cache();

        return $cache->remember(sprintf(CacheEnums::RSS_KEY_TEMPLATE, $source, $type, $page, $size), CacheEnums::RSS_EXPIRE_TIME, function () use ($source, $type, $page, $size) {

            $rss = new \Business\Impl\Rss();

            $data = $rss->loadRss($source, $type, $page, $size);

            return $data;
        });
    }


}
