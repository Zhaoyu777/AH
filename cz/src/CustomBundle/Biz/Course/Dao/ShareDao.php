<?php

namespace CustomBundle\Biz\Course\Dao;

interface ShareDao
{
    public function findByFromUserId($fromUserId);

    public function findByToUserId($toUserId);

    public function findByCourseId($courseId);
}
