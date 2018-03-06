<?php

namespace CustomBundle\Biz\DisplayWall\Dao;

interface LikeDao
{
    public function deleteByContentIdAndUserId($contentId, $userId);

    public function getByContentIdAndUserId($contentId, $userId);
}
