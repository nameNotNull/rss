<?php
use MobileApi\Util\Config;
class Mapping_Search_Controller extends Base_Controller
{
    public function process()
    {
        $key = $this->getParam('key');

        if (!empty($key)) {
            return Config::get('mapping.' . trim(trim($key), '.'), []);
        } else {
            return array_values(Config::get('mapping.rss.config.source', []));
        }
    }
}
