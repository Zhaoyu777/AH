<?php

namespace CustomBundle\Biz\Statistics\Dao;

interface CourseStatisticsDao
{
    public function findByCourseId($courseId);

    public function findTeachingAimWarningCoursesByValue($value);
}
