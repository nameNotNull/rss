<?php

use MobileApi\Api\Mail;
use MobileApi\Util\Config;
use MobileApi\Util\DB;

class Console_Order_Update_Controller extends Base_Controller
{
    public function process()
    {
        $minId = 0;
        while (true) {
            $list = Dao_Work_Model::getInstance()->where('id', '>', $minId)->limit(100)->get();
            if (empty($list->toArray())) {
                break;
            }

            $workIds = array_column($list->toArray(), 'id');

            $orderList = Dao_Order_Model::getInstance()->getListByWorkIds($workIds)->toArray();

            $orderList = array_column($orderList, null, 'work_id');
            foreach ($list as $item) {
                $item->is_bound = false;
                if (isset($orderList[$item['id']])) {
                    $item->is_bound = true;
                }
                $item->save();
                $minId = $item['id'];
            }

        }

        return true;
    }
}