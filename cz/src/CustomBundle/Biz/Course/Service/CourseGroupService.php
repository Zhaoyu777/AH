<?php

namespace CustomBundle\Biz\Course\Service;

interface CourseGroupService
{
    public function createCourseGroup($fields);

    public function deleteGroup($id);

    public function getCourseGroup($groupId);

    public function findCourseGroupsByCourseIdWithMembers($courseId, $withMember);
}
