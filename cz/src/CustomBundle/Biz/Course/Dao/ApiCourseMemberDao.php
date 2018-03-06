<?php

namespace CustomBundle\Biz\Course\Dao;

interface ApiCourseMemberDao
{
    public function getByCourseIdAndNo($courseId, $no);

    public function findByMemberIds($memberIds);
}
