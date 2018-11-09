<?php

namespace Business\Impl\Rss;

use MobileApi\Util\Config;
use Utils\Rss;

class Sina extends \Business\Base\Rss
{

    protected function _load($type, $page, $size)
    {
        $types = Config::get('mapping.rss.sina.types');

        $url = Config::get('mapping.rss.sina.type.' . $type . '.url') . "?page={$page}&size={$size}";

        $result = [];
        if ($type == $types['daily']) {
            $rss  = Rss::httpRequest($url);
            $list = json_decode($rss, true);

            foreach ($list as $item) {
                $tmp = [
                    'id'      => $item['newsId'],
                    'title'   => $item['title'],
                    'images'  => $item['pic'],
                    'content' => '<img referrerpolicy="no-referrer" src="' . $item['pic'] . '">',
                    'link'    => '',
                    'source'  => 'sina',
                    'read_num'     => '',
                    'publish_time' => '',
                    'source_child' => $item['source'],
                ];
                array_push($result, $tmp);
            }
            $result = array_values($result);

        } else {
            $rss  = Rss::loadRss($url);
            $list = $rss->toArray();
            $list = array_slice($list['item'], 0, 10);
            foreach ($list as $item) {
                $tmp = [
                    'id'      => '',
                    'title'   => $item['title'],
                    'content' => $item['description'],
                    'link'    => '',
                    'source'  => $type,
                ];
                array_push($result, $tmp);
            }
            $result = array_values($result);
        }

        return $result;
    }

    protected function _loadDetail($type, $id)
    {

        $url = Config::get('mapping.rss.sina.type.' . $type . '.detailurl') . $id;

        $rss  = Rss::httpRequest($url);
        $info = json_decode($rss, true);

        $result = [];

        if (!empty($info['data'])) {
            $result = [
                'id'       => $info['data']['newsId'],
                'title'    => $info['data']['title'],
                'content'  => $info['data']['content'],
                'css'      => '',
                'head_img' => $info['data']['pics'][0] ? $info['data']['pics'][0]['data']['pic'] : '',
            ];
        }

        return $result;
    }

    protected function rule()
    {

    }
}