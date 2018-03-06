<?php

namespace CustomBundle\Biz\User\Service;

use Biz\User\Service\UserService as BaseUserService;

interface UserService extends BaseUserService
{
    public function searchAllUsers(array $conditions, array $orderBy, $start, $limit);
}
