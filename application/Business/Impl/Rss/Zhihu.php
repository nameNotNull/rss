<?php

namespace Business\Impl\Rss;

use MobileApi\Util\Config;
use Utils\Rss;

class Zhihu extends \Business\Base\Rss
{

    protected function _load($type)
    {
        $types = Config::get('mapping.rss.zhihu.types');

        $url = Config::get('mapping.rss.zhihu.type.' . $type . '.url');

        $result = [];
        if ($type == $types['daily']) {
            $rss  = Rss::httpRequest($url);
            $list = json_decode($rss, true);

            foreach ($list['stories'] as $item) {
                $tmp = [
                    'id'      => $item['id'],
                    'title'   => $item['title'],
                    'images'   => $item['images'][0],
                    'content' => '<img referrerpolicy="no-referrer" src="' . $item['images'][0] . '">',
                    'link'    => '',
                ];
                array_push($result, $tmp);
            }
            $result = array_values($result);

        } else {
            $rss    = Rss::loadRss($url);
            $list   = $rss->toArray();
            $list = array_slice($list['item'], 0, 10);
            foreach ($list as $item) {
                $tmp = [
                    'id'      => '',
                    'title'   => $item['title'],
                    'content' => $item['description'],
                    'link'    => '',
                ];
                array_push($result, $tmp);
            }
            $result = array_values($result);
        }
        return $result;
    }

    protected function _loadDetail($type, $id)
    {

        $url = 'https://news-at.zhihu.com/api/4/news/'.$id;


        $rss  = Rss::httpRequest($url);
        $list = json_decode($rss, true);

        $result = [
                'id'      => $list['id'],
                'title'   => $list['title'],
                'content' => $list['body'],
                'css'    => $list['css'],
                'head_img'    => $list['images'],
            ];

        return $result;
    }

    protected function rule()
    {

    }
}