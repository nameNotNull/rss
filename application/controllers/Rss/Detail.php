<?php

use MobileApi\Util\Cache;
use Enums\Cache as CacheEnums;

class Rss_Detail_Controller extends Base_Controller
{

    public function process()
    {
        $source = $this->getParam('source', 0);
        $type   = $this->getParam('type', 0);
        $id     = $this->getParam('id', 0);

        $cache = new Cache();

        return $cache->remember(sprintf(CacheEnums::RSS_DETAIL_KEY_TEMPLATE, $source, $type,$id), CacheEnums::RSS_DETAIL_EXPIRE_TIME, function () use ($source, $type, $id) {

            $rss = new \Business\Impl\Rss();

            $data = $rss->loadRssDetail($source, $type, $id);

            return $data;
        });
    }


}
