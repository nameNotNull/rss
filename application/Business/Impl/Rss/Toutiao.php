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
                $img     = isset($content['image_list']) & isset($content['image_list'][0]) ? substr($content['image_list']['0']['url'], 0, strlen($content['image_list']['0']['url']) - 5) : '';
                preg_match('/.*\/(\d+)\//', $content['display_url'], $match);
                $id = 0;
                if (!empty($match) && !empty($match[1])) {
                    $id = $match[1];
                }
                $tmp = [
                    'id'           => $id,
                    'title'        => mb_strlen($content['title']) > 40 ? mb_substr($content['title'], 0, 40) . '...' : $content['title'],
                    'images'       => empty($img) ? '' : $img,
                    'content'      => '<img referrerpolicy="no-referrer" src="' . $img . '">',
                    'link'         => '',
                    'source'       => 'toutiao',
                    'read_num'     => '阅读量'.$content['read_count'] ?? '',
                    'publish_time' => $content['publish_time'] ? date('Y-m-d H:i:s', $content['publish_time']) : '',
                    'source_child' => $content['source'] ?? '',
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