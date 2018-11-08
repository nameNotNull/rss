<?php

namespace Business\Impl\Rss;

use MobileApi\Util\Config;
use Utils\Rss;

class Toutiao extends \Business\Base\Rss
{

    protected function _load($type, $page, $size)
    {
        $types = Config::get('mapping.rss.toutiao.types');

        $url = Config::get('mapping.rss.toutiao.type.' . $type . '.url');

        $result = [];
        if ($type == $types['daily']) {
            $url  = str_replace("xxxxxx", time(), $url);
            $rss  = Rss::httpRequest($url);
            $list = json_decode($rss, true);

            foreach ($list['data'] as $item) {
                $content = json_decode($item['content'], true);
                $img     = $content['middle_image']['url'] ?? '';
                preg_match('/.*\/(\d+)\//', $content['display_url'], $match);
                $id = 0;
                if (!empty($match) && !empty($match[1])) {
                    $id = $match[1];
                }
                $tmp = [
                    'id'      => $id,
                    'title'   => $content['title'] ?? '',
                    'images'  => $img,
                    'content' => '<img referrerpolicy="no-referrer" src="' . $img . '">',
                    'link'    => '',
                    'source'  => 'toutiao',
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

        $url = Config::get('mapping.rss.toutiao.type.' . $type . '.detailurl') . $id . '/';
        $rss = Rss::httpRequest($url);

        $info = json_decode($rss, true);

        $result = [];

        $content = '';

        if (!empty($info['data'])) {
            $contentList = is_array($info['data']['content']) ? $info['data']['content'] : '';
            if (!empty($contentList)) {
                foreach ($contentList as $item) {
                    if (is_array($item)) {
                        $text = '<img referrerpolicy="no-referrer" src="' . $item['info']['src'] . '">';
                    } else {
                        $text = '<p>' . $item . '</p>';
                    }
                    $content .= $text;
                }

            }
            $result = [
                'id'       => '',
                'title'    => $info['data']['title'] ?? '',
                'content'  => $content,
                'css'      => '',
                'head_img' => '',
            ];
        }

        return $result;
    }


    protected function rule()
    {

    }
}