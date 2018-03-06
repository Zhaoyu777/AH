<?php

namespace CustomBundle\Biz\Statistics\Dao;

interface StudentCourseStatisticsDao
{
    public function getStudentsMultiAnalysisByCourseId($courseId);

    public function getByUserIdAndCourseId($userId, $courseId);
}
