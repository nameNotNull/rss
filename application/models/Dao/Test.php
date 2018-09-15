<?php

/**
 * Class     Dao_Test_Model
 *
 * @author   wangxuan
 */
class Dao_Test_Model extends Dao_Base_Model
{
    protected $table = 'test';

    protected $primaryKey = 'id';

    protected $fillable = [
        'user_id',
        'name',
        'mobile',
        'address',
        'status',
        'create_time',
        'modify_time',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'status'  => 'integer',
    ];


    /**
     * Method  getListByUserId
     *
     * Note
     *
     * @author wangxuan
     *
     * @param $userId
     *
     * @return mixed
     */
    public function getListByUserId($userId)
    {
        return $this->where('user_id', (int)$userId)->get();
    }
}
