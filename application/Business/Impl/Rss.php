<?php

namespace Business\Impl;

class Rss
{
    function loadRss($source, $type)
    {
        $obj  = \Business\Factory\Rss::create($source);
        return $obj->load($type);
    }
}