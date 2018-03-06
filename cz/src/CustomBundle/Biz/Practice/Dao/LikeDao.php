<?php

namespace CustomBundle\Biz\Practice\Dao;

interface LikeDao
{
    public function getByContentIdAndUserId($contentId, $userId);

    public function deleteByContentId($contentId);

    public function deleteByContentIdAndUserId($contentId, $userId);
}
