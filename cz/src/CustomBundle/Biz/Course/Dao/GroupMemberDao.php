<?php

namespace CustomBundle\Biz\Course\Dao;

interface GroupMemberDao
{
    public function getByCourseMemberId($courseMemberId);

    public function deleteByGroupId($groupId);

    public function findByGroupIds($groupIds);

    public function getMaxSeqByGroupId($groupId);

    public function deleteByCourseMemberId($courseMemberId);
}
