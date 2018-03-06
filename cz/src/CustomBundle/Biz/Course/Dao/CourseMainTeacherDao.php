<?php

namespace CustomBundle\Biz\Course\Dao;

interface CourseMainTeacherDao
{
    public function getByCourseId($courseId);

    public function findCoursesByTeacherId($teacherId);

    public function findMainTeachersByCourseIds($courseIds);

    public function deleteByCourseId($courseId);
}
