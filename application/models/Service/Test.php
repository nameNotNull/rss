<?php

use Utils\Convert;
use MobileApi\Exception\Exception;
use Yaf\Registry;

class Service_Test_Model extends Service_Base_Model
{

    public function __construct()
    {
    }

    /**
     * Method  getListByUserId
     * Note
     *
     * @author wangxuan
     *
     * @param $userId
     *
     * @return array|mixed
     */
    public function getListByUserId($userId)
    {
        if (empty($userId)) {
            return [];
        }

        $list = Dao_Test_Model::getInstance()->getListByUserId($userId);

        if (empty($list)) {
            throw new Exception('resource_not_found');
        }

        return $list->toArray();
    }
}
