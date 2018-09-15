<?php

use MobileApi\Exception\Exception;

class Service_Base_Model
{

    protected static $instance;

    /**
     * Method  getInstance
     *
     * @static
     * @return static
     */
    public static function getInstance()
    {
        if (static::$instance instanceof static) {
            return static::$instance;
        }

        return static::$instance = new static;
    }

    /**
     * Method  getDaoInstance
     *
     * @author wangxuan
     * @static
     * @throws Exception
     * @return mixed
     */
    protected static function getDaoInstance()
    {
        $daoClassName = preg_replace('/^Service_/', 'Dao_', get_called_class());

        if (class_exists($daoClassName) === false) {
            throw new Exception('class_not_found');
        }

        return ($daoClassName)::getInstance();
    }

    /**
     * Method  create
     *
     * @author wangxuan
     *
     * @param array $data
     *
     * @throws Exception
     * @return mixed
     */
    public function create(array $data)
    {
        $object = static::getDaoInstance()->create(array_filter($data, function ($value) {
            return ($value !== null);
        }));

        if (empty($object->id)) {
            throw new Exception('resource_database_insert_failed');
        }

        return $object->toArray();
    }

    /**
     * Method  modify
     *
     * @author wangxuan
     *
     * @param array $data
     *
     * @throws Exception
     * @return bool
     */
    public function modify(array $data)
    {
        $object = static::getDaoInstance()->getById($data['id']);

        if (empty($object)) {
            throw new Exception('resource_not_found');
        }

        $result = static::getDaoInstance()->whereId($data['id'])->update(array_filter($data, function ($value) {
            return ($value !== null);
        }));

        if ($result === false) {
            throw new Exception('resource_database_update_failed');
        }

        return true;
    }

    /**
     * Method  remove
     *
     * @author wangxuan
     *
     * @param array $ids
     *
     * @throws Exception
     * @return bool
     */
    public function remove(array $ids)
    {
        if (empty($ids)) {
            return false;
        }

        $result = static::getDaoInstance()->whereIn('id', $ids)->delete();

        if ($result === false) {
            throw new Exception('resource_database_delete_failed');
        }

        return true;
    }

    /**
     * Method  removeByWorkId
     *
     * @author wangxuan
     *
     * @param int $workId
     *
     * @throws Exception
     * @return bool
     */
    public function removeByWorkId($workId)
    {
        if (empty($workId)) {
            return false;
        }

        $result = static::getDaoInstance()->where('work_id', (int)$workId)->delete();

        if ($result === false) {
            throw new Exception('resource_database_delete_failed');
        }

        return true;
    }

    /**
     * Method  getInfo
     *
     * @author wangxuan
     *
     * @param int $id
     *
     * @throws Exception
     * @return mixed
     */
    public function getInfo($id)
    {
        $object = static::getDaoInstance()->getById($id);

        if (empty($object)) {
            throw new Exception('resource_not_found');
        }

        return $object->toArray();
    }

    /**
     * Method  getInfoByWorkId
     *
     * @author wangxuan
     *
     * @param $workId
     *
     * @throws Exception
     * @return mixed
     */
    public function getInfoByWorkId($workId)
    {
        $object = static::getDaoInstance()->getByWorkId($workId);

        if (empty($object)) {
            throw new Exception('resource_not_found');
        }

        return $object->toArray();
    }

    /**
     * Method  getListByUserId
     *
     * @author wangxuan
     *
     * @param $userId
     *
     * @throws Exception
     * @return mixed
     */
    public function getListByUserId($userId)
    {
        $object = static::getDaoInstance()->getListByUserId($userId);

        if (empty($object)) {
            throw new Exception('resource_not_found');
        }

        return $object->toArray();
    }

    /**
     * Method  isBelongsToUserId
     *
     * @author wangxuan
     *
     * @param int|string $id
     * @param string     $userId
     *
     * @throws Exception
     * @return bool
     */
    public function isBelongsToUserId($id, $userId)
    {
        if (is_numeric($id)) {
            $userIds = static::getDaoInstance()->where('id', (int)$id)->lists('user_id')->toArray();
        } else {
            $userIds = static::getDaoInstance()->distinct()->whereIn('id', array_map('intval', explode(',', $id)))->lists('user_id')->toArray();
        }

        if (empty($userIds) || count($userIds) !== 1 || (string)current($userIds) !== (string)$userId) {
            throw new Exception('resource_does_not_belongs_to_you');
        } else {
            return true;
        }
    }

}
