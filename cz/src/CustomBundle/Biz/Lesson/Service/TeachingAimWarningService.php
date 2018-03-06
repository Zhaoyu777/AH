<?php

namespace CustomBundle\Biz\Lesson\Service;

interface TeachingAimWarningService
{
    public function batchCreate($rows);

    public function addWarningTimeByCourseIds($courseIds);

    public function deleteByCourseIds($courseIds);

    public function findAllWarningCourses();

    public function findTeachingAimWarningCoursesByCourseIds($courseIds);
}
