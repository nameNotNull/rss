<?php

use MobileApi\Util\Convert;
use MobileApi\Exception\Exception;

class Test_Show_Controller extends Base_Controller
{

    public function process()
    {
        $userId = Convert::toInteger($this->getParam('user_id', 0));

        try {
            $list = Service_Test_Model::getInstance()->getListByUserId($userId);
        } catch (Exception $exception) {
            if ($exception->getMessage() !== 'Resource not found') {
                throw $exception;
            }
            $list = [];
        }

        return $list;
    }

}
