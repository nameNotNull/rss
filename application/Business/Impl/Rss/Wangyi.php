<?php

namespace Business\Impl\Rss;

use MobileApi\Util\Config;
use Utils\Rss;

class Wangyi extends \Business\Base\Rss
{

    protected function _load($type, $page, $size)
    {
        $types = Config::get('mapping.rss.wangyi.types');

        $url = Config::get('mapping.rss.wangyi.type.' . $type . '.url');

        $result = [];
        if ($type == $types['daily']) {
            $url  = str_replace("xxxxxx", time(), $url);
            $rss  = Rss::httpRequest($url);
            $list = json_decode($rss, true);

            foreach ($list['data'] as $item) {
                $tmp = [
                    'id'           => $item['docid'],
                    'title'        => $item['title'],
                    'images'       => $item['imgsrc'],
                    'content'      => '<img referrerpolicy="no-referrer" src="' . $item['imgsrc'] . '">',
                    'link'         => '',
                    'source'       => 'wangyi',
                    'read_num'     => '',
                    'publish_time' => $item['ptime'] ?? '',
                    'source_child' => $item['source'] ?? '',
                ];
                array_push($result, $tmp);
            }
            $result = array_slice(array_values($result), 2);

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

        $url = Config::get('mapping.rss.wangyi.type.' . $type . '.detailurl');

        $url  = str_replace("xxxxxx", $id, $url);
        $rss  = file_get_contents($url);
        $list = json_decode($rss, true);

        $content = '';
        if (!empty($list[$id])) {
            $img = $list[$id]['img'];
            foreach ($img as $item) {
                $text = '';
                if (is_array($item)) {
                    $text = '<img referrerpolicy="no-referrer" src="' . $item['src'] . '">';
                }
                $content .= $text;
            }
            $content .= $list[$id]['body'];
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