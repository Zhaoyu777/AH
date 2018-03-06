<?php

namespace CustomBundle\Biz\Course\Dao;

interface CourseLessonDao
{
    public function getCurrenTeachCourseLesson($courseId);

    public function findByIds($ids);

    public function findCourseLessonCountByCourseIds($courseIds);

    public function findByCourseIdAndStatus($courseId, $status);

    public function countSchoolTasksByTermCode($termCode);
}
