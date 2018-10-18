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

//        $url   = Config::get('mapping.rss.' . $source . '.type.' . $type);
        $cache = new Cache();

        //        echo 'Title: ', $rss->title;
        //        echo 'Description: ', $rss->description;
        //        echo 'Link: ', $rss->link;
        //
        //        foreach ($rss->item as $item) {
        //            echo 'Title: ', $item->title;
        //            echo 'Link: ', $item->link;
        //            echo 'Timestamp: ', $item->timestamp;
        //            echo 'Description ', $item->description;
        //            echo 'HTML encoded content: ', $item->{'content:encoded'};
        //        }

        return $cache->remember(sprintf(CacheEnums::RSS_KEY_TEMPLATE, $source, $type), CacheEnums::RSS_EXPIRE_TIME, function () use ($source,$type) {

            $rss = new \Business\Impl\Rss();

            $data = $rss->loadRss($source,$type);

            return $data;
        });
    }


}
