<?php

namespace CustomBundle\Biz\User\Dao;

use Biz\User\Dao\UserDao as BaseUserDao;

interface UserDao extends BaseUserDao
{
    public function searchAllUsers(array $conditions, array $orderBy, $start, $limit);
}
