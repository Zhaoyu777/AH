<?php

namespace CustomBundle\Biz\Course\Service;

interface GroupMemberService
{
    public function createGroupMember($member);

    public function deleteGroupMember($id);

    public function deleteGroupMemberByGroupId($groupId);

    public function batchCreateGroupMembers($groupId, $memberIds);

    public function getGroupMember($id);

    public function getGroupMemberByCourseMemberId($courseMemberId);

    public function findGroupMembersByGroupIdsWithUserId($groupIds, $withItem);

    public function hasCourseGroup($courseMemberId, $courseId);

    public function getMaxSeqByGroupId($groupId);

    public function deleteGroupMemberByCourseMemberId($courseMemberId);
}
