<?php

namespace MobileApi\Util\User;

interface LoginInfoInterface
{
    /**
     * 根据token获得登陆信息
     *
     * @param $token
     *
     * @return mixed
     */
    public function get($token);

    /**
     * 根据token销毁登陆信息
     *
     * @param $token
     *
     * @return mixed
     */
    public function destroy($token);
}