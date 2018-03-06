<?php

namespace CustomBundle\Biz\Course\Service\Impl;

use Biz\BaseService;
use AppBundle\Common\ArrayToolkit;
use Codeages\Biz\Framework\Event\Event;
use CustomBundle\Biz\Course\Service\CourseGroupService;

class CourseGroupServiceImpl extends BaseService implements CourseGroupService
{
    public function createCourseGroup($fields)
    {
        if (!ArrayToolkit::requireds($fields, array('courseId'))) {
            throw $this->createInvalidArgumentException('Lack of required fields');
        }
        $group = ArrayToolkit::parts($fields, array(
            'title',
            'courseId',
            'type',
        ));
        $group['seq'] = 0;
        $group['number'] = $this->getMaxGroupNumberByCourseId($fields['courseId']);
        if ($group['number'] == null) {
            $group['number'] = 0;
        } else {
            $group['number']++;
        }

        if (empty($group['title'])) {
            $group['title'] = "第{$group['number']}组";
        }

        $this->beginTransaction();
        try {
            $created = $this->getGroupDao()->create($group);

            $this->commit();

            $this->dispatchEvent('courseGroup.create', new Event($created));
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }

        if (!empty($fields['memberIds'])) {
            $this->getGroupMemberService()->batchCreateGroupMembers($created['id'], $fields['memberIds']);
        }

        return $created;
    }

    public function getMaxGroupNumberByCourseId($courseId)
    {
        return $this->getGroupDao()->getMaxGroupNumberByCourseId($courseId);
    }

    public function deleteGroup($id)
    {
        $group = $this->getCourseGroup($id);

        if (empty($group)) {
            throw $this->createNotFoundException('group not found');
        }

        if ($group['type'] == 'default') {
            throw $this->createAccessDeniedException('不能删除默认分组');
        }

        $result = $this->getGroupDao()->delete($id);

        $this->dispatchEvent('course.group.delete', new Event($group));

        return $result;
    }

    public function getCourseGroup($groupId)
    {
        return $this->getGroupDao()->get($groupId);
    }

    public function findCourseGroupsByCourseIdWithMembers($courseId, $withMember = false)
    {
        $groups = $this->getGroupDao()->findByCourseId($courseId);

        if ($withMember) {
            $students = $this->getMemberService()->findCourseStudents($courseId, 0, PHP_INT_MAX);
            $students = ArrayToolkit::index($students, 'id');

            $groupIds = ArrayToolkit::column($groups, 'id');
            $groupMembers = $this->getGroupMemberService()->findGroupMembersByGroupIdsWithUserId($groupIds, true);
            $exMemberIds = array_diff(ArrayToolkit::column($students, 'id'), ArrayToolkit::column($groupMembers, 'courseMemberId'));
            $groupMembers = ArrayToolkit::group($groupMembers, 'groupId');

            foreach ($groups as &$group) {
                if (!empty($groupMembers[$group['id']])) {
                    $group['members'] = $groupMembers[$group['id']];
                } else {
                    $group['members'] = array();
                }
            }
        }

        return $groups;
    }

    public function getDefaultGroupByCourseId($courseId)
    {
        return $this->getGroupDao()->getDefaultByCourseId($courseId);
    }

    protected function getGroupDao()
    {
        return $this->createDao('CustomBundle:Course:GroupDao');
    }

    protected function getGroupMemberService()
    {
        return $this->createService('CustomBundle:Course:GroupMemberService');
    }

    protected function getMemberService()
    {
        return $this->createService('CustomBundle:Course:MemberService');
    }
}
