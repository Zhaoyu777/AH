<?php

namespace CustomBundle\Biz\Course\Service\Impl;

use Biz\BaseService;
use AppBundle\Common\ArrayToolkit;
use Codeages\Biz\Framework\Event\Event;
use CustomBundle\Biz\Course\Service\GroupMemberService;

class GroupMemberServiceImpl extends BaseService implements GroupMemberService
{
    public function createGroupMember($member)
    {
        if (!ArrayToolkit::requireds($member, array('groupId', 'courseMemberId'))) {
            throw $this->createInvalidArgumentException('Lack of required member');
        }

        $member = ArrayToolkit::parts($member, array(
            'groupId',
            'courseMemberId',
            'seq'
        ));

        $group = $this->getCourseGroupService()->getCourseGroup($member['groupId']);
        if ($this->hasCourseGroup($member['courseMemberId'], $group['courseId'])) {
            throw $this->createInvalidArgumentException("courseMemberId:{$member['courseMemberId']} has group in course:{$group['courseId']}");
        }

        $member['seq'] = empty($member['seq']) ? 0 : $member['seq'];

        $this->beginTransaction();
        try {
            $created = $this->getGroupMemberDao()->create($member);
            $this->commit();

            $this->dispatchEvent('courseMember.create', new Event($created));

            return $created;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function getMaxSeqByGroupId($groupId)
    {
        return $this->getGroupMemberDao()->getMaxSeqByGroupId($groupId);
    }

    public function deleteGroupMember($id)
    {
        $groupMember = $this->getGroupMember($id);

        if (empty($groupMember)) {
            throw $this->createNotFoundException('group member not found');
        }

        return $this->getGroupMemberDao()->delete($id);
    }

    public function resetGroupMemberGroupId($groupId, $defaultGroupId)
    {
        $groupMembers = $this->getGroupMemberDao()->findByGroupIds(array($groupId));
        $maxGroup = $this->getGroupMemberDao()->getMaxSeqByGroupId($defaultGroupId);
        if (empty($maxGroup)) {
            return;
        }

        $seq = $maxGroup['seq'];
        foreach ($groupMembers as $groupMember) {
            $files = array(
                'groupId' => $defaultGroupId,
                'seq' => ++$seq,
            );
            $this->getGroupMemberDao()->update($groupMember['id'], $files);
        }
    }

    public function deleteGroupMemberByGroupId($groupId)
    {
        return $this->getGroupMemberDao()->deleteByGroupId($groupId);
    }

    public function batchCreateGroupMembers($groupId, $memberIds)
    {
        $memberIds = array_filter($memberIds);

        foreach ($memberIds as $memberId) {
            $this->setGroup($memberId, $groupId);
        }
    }

    public function sortCourseGroupMembers($courseId, $ids)
    {
        $groupId = 0;
        $seq = 1;
        foreach ($ids as $id) {
            $result = explode('-', $id);
            if ($result[0] == 'group') {
                $groupId = $result[1];
                $seq = 1;
                continue;
            }

            if ($result[0] == 'member' && !empty($result[1])) {
                $this->setGroup($result[1], $groupId, $seq);
                $seq ++;
            }
        }
    }

    public function setGroup($courseMemberId, $groupId, $seq = 0)
    {
        $groupMember = $this->getGroupMemberByCourseMemberId($courseMemberId);

        if (empty($groupMember)) {
            return $this->createGroupMember(array('courseMemberId' => $courseMemberId, 'groupId' => $groupId, 'seq' => $seq));
        }

        return $this->getGroupMemberDao()->update($groupMember['id'], array('groupId' => $groupId, 'seq' => $seq));
    }

    public function getGroupMember($id)
    {
        return $this->getGroupMemberDao()->get($id);
    }

    public function getGroupMemberByCourseMemberId($courseMemberId)
    {
        return $this->getGroupMemberDao()->getByCourseMemberId($courseMemberId);
    }

    public function findGroupMembersByGroupIdsWithUserId($groupIds, $withItem = false)
    {
        $groupMembers = $this->getGroupMemberDao()->findByGroupIds($groupIds);

        if ($withItem) {
            $memberIds = ArrayToolkit::column($groupMembers, 'courseMemberId');
            $courseMembers = $this->getMemberService()->findMembersByIdsWithUserInfo($memberIds, true);
            $userIds = ArrayToolkit::column($courseMembers, 'userId');
            $users = $this->getUserService()->findUsersByIds($userIds);
            $userProfiles = $this->getUserService()->findUserProfilesByIds($userIds);

            $apiCourseMembers = $this->getMemberService()->findApiMembersByMemberIds($memberIds);
            $apiCourseMemberIds = ArrayToolkit::column($apiCourseMembers, 'memberId');

            foreach ($groupMembers as &$groupMember) {
                if (!empty($courseMembers[$groupMember['courseMemberId']])) {
                    $courseMember = $courseMembers[$groupMember['courseMemberId']];
                    $groupMember['userId'] = $courseMember['userId'];
                    $groupMember['nickname'] = $courseMember['nickname'];
                    $groupMember['email'] = $courseMember['email'];
                    $groupMember['roles'] = $courseMember['roles'];
                    $groupMember['number'] = $courseMember['number'];
                    $groupMember['truename'] = $userProfiles[$courseMember['userId']]['truename'];
                    $groupMember['from'] = in_array($courseMember['id'], $apiCourseMemberIds) ? 'import' : 'add';
                }
            }
        }

        return $groupMembers;
    }

    public function findGroupMemberUserIdsByGroupIdAndCourseMemberIds($groupId, $courseMemberIds)
    {
        $groupMembers = $this->getGroupMemberDao()->findByGroupIdAndCourseMemberIds($groupId, $courseMemberIds);

        $memberIds = ArrayToolkit::column($groupMembers, 'courseMemberId');
        $courseMembers = $this->getMemberService()->findMembersByIdsWithUserInfo($memberIds, true);

        return ArrayToolkit::column($courseMembers, 'userId');
    }

    public function hasCourseGroup($courseMemberId, $courseId)
    {
        $groups = $this->getCourseGroupService()->findCourseGroupsByCourseIdWithMembers($courseId);
        $groupIds = ArrayToolkit::column($groups, 'id');
        $members = $this->findGroupMembersByGroupIdsWithUserId($groupIds);
        $memberIds = ArrayToolkit::column($members, 'courseMemberId');

        if (in_array($courseMemberId, $memberIds)) {
            return true;
        }

        return false;
    }

    public function deleteGroupMemberByCourseMemberId($courseMemberId)
    {
        if (empty($courseMemberId)) {
            return;
        }

        if (!$this->getGroupMemberByCourseMemberId($courseMemberId)) {
            return;
        }

        return $this->getGroupMemberDao()->deleteByCourseMemberId($courseMemberId);
    }

    public function countGroupMembersByGroupId($groupId)
    {
        return $this->getGroupMemberDao()->count(array('groupId' => $groupId));
    }

    protected function getCourseGroupService()
    {
        return $this->createService('CustomBundle:Course:CourseGroupService');
    }

    protected function getGroupMemberDao()
    {
        return $this->createDao('CustomBundle:Course:GroupMemberDao');
    }

    protected function getMemberService()
    {
        return $this->createService('CustomBundle:Course:MemberService');
    }

    protected function getUserService()
    {
        return $this->createService('User:UserService');
    }
}
