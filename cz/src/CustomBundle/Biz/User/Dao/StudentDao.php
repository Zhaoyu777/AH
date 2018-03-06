<?php

namespace CustomBundle\Biz\User\Dao;

interface StudentDao
{
    public function getByCode($code);
}
