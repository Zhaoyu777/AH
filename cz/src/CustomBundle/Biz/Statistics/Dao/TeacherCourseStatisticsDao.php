<?php

namespace CustomBundle\Biz\Statistics\Dao;

interface TeacherCourseStatisticsDao
{
    public function getByUserId($userId);
}
