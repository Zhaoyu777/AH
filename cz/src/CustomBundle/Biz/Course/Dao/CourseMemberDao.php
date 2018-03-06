<?php

namespace CustomBundle\Biz\Course\Dao;

use Biz\Course\Dao\CourseMemberDao as BaseCourseMemberDao;

interface CourseMemberDao
{
    public function findStudentCountsByCourseIds($courseIds);

    public function randStudentByCourseId($courseId, $start, $exUserIds);

    public function findByIds($ids);

    public function findStudentWithScore($courseId, $start, $limit);

    public function deleteByCourseMemberId($courseMemberId);
}
