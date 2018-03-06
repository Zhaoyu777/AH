<?php

namespace CustomBundle\Biz\Course\Dao;

use Biz\Course\Dao\CourseDao as BaseCourseDao;

interface CourseDao extends BaseCourseDao
{
    public function findNotClosedCourseCountsBySetIdsAndTeacherId($courseSetIds, $teacherId);

    public function findNotClosedCoursesByTeacherId($teacherId);

    public function findNotClosedCoursesByTeacherIdAndTermCode($teacherId, $termCode);

    public function findNormalCoursesByIds($ids);
}
