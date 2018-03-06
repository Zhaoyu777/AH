<?php

namespace CustomBundle\Biz\DisplayWall\Dao;

interface PostDao
{
    public function findByContentId($contentId);
}
