<?php

namespace Business\Impl\Rss;

use MobileApi\Util\Config;
use Utils\Rss;

class Zhihu extends \Business\Base\Rss
{

    protected function _load($type, $page, $size)
    {
        $types = Config::get('mapping.rss.zhihu.types');

        $url = Config::get('mapping.rss.zhihu.type.' . $type . '.url');

        $result = [];
        if ($type == $types['daily']) {
            $rss  = Rss::httpRequest($url);
            $list = json_decode($rss, true);

            foreach ($list['data'] as $item) {
                $tmp = [
                    'id'      => substr($item['card_id'], 2),
                    'title'   => $item['target']['title_area']['text'],
                    'images'  => $item['target']['image_area']['url'],
                    'content' => '<img referrerpolicy="no-referrer" src="' . $item['target']['image_area']['url'] . '">',
                    'link'    => '',
                    'source'  => 'zhihu',
                    'read_num'     => $item['target']['metrics_area']['text'] ?? 0,
                    'publish_time' => '',
                    'source_child' => '',
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
                ];
                array_push($result, $tmp);
            }
            $result = array_values($result);
        }

        return $result;
    }

    protected function _loadDetail($type, $id)
    {

        $url = Config::get('mapping.rss.zhihu.type.' . $type . '.answersurl');

        $url  = str_replace("xxxxxx", $id, $url);
        $rss  = file_get_contents($url);
        $list = json_decode($rss, true);

        $content = '';
        if (!empty($list['data'][0])) {
            $detailUrl  = Config::get('mapping.rss.zhihu.type.' . $type . '.detailurl');
            $detail     = file_get_contents(str_replace("xxxxxx", $list['data'][0]['id'], $detailUrl));
            $detailInfo = json_decode($detail, true);
            $content    = $detailInfo['content'];
        }
        $result = [
            'id'       => '',
            'title'    => '',
            'content'  => $content,
            'css'      => '',
            'head_img' => '',
        ];

        return $result;
    }



    protected function rule()
    {

    }
}