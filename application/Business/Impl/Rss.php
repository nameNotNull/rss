<?php

namespace Business\Impl;

class Rss
{
    function loadRss($source, $type, $page, $size)
    {
        $obj = \Business\Factory\Rss::create($source);

        return $obj->load($type, $page, $size);
    }

    function loadDetail($source, $type, $id)
    {
        $obj = \Business\Factory\Rss::create($source);

        return $obj->loadDetail($type, $id);
    }

}