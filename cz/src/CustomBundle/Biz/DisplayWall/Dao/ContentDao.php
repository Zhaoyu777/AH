<?php

namespace CustomBundle\Biz\DisplayWall\Dao;

interface ContentDao
{
    public function getLastByResultId($resultId);

    public function findByUserIds($userIds);

    public function findByResultIds($resultIds);

    public function getByResultIdAndUserId($resultId, $userId);
}
