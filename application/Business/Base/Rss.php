<?php

namespace Business\Base;

/**
 * Class Message
 *
 * @package    business\base
 * @inheritdoc 消息的类定义
 */
abstract class Rss
{
    protected function rule()
    {
    }


    abstract protected function _load($params);

    final function load($params)
    {
        $this->valid();
        $this->rule();

        return $this->_load($params);
    }

    abstract protected function _loadDetail($type, $id);

    final function loadDetail($type, $id)
    {
        return $this->_loadDetail($type, $id);
    }

    final function valid()
    {

    }
}