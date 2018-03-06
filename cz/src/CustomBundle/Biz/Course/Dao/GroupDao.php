<?php

namespace CustomBundle\Biz\Course\Dao;

interface GroupDao
{
    public function findByCourseId($courseId);
}
