<?php

namespace CustomBundle\Biz\Lesson\Dao;

interface CourseTeachingAimWarningDao
{
    public function waveByCourseIds(array $courseIds, array $diffs);

    public function deleteByCourseIds($courseIds);

    public function findAllWarningCourses();

    public function findByCourseIds($courseIds);
}
