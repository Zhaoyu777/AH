<?php

namespace CustomBundle\Biz\Course\Service;

interface CourseShareService
{
    public function createCourseShare($share);

    public function getCourseShare($id);

    public function findCourseSharesByFromUserId($fromUserId);

    public function findCourseSharesByToUserId($toUserId);

    public function findCourseSharesByCourseId($courseId);

    public function deleteCourseShare($id);

    public function countShareByTimeRangeAndOrgCodeGroupType($startTime, $endTime, $orgCode);
}
