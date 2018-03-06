<?php

namespace CustomBundle\Biz\Course\Service\Impl;

use Biz\BaseService;
use AppBundle\Common\ArrayToolkit;
use Codeages\Biz\Framework\Event\Event;
use CustomBundle\Biz\Course\Service\ShareDao;
use CustomBundle\Biz\User\Service\UserService;
use CustomBundle\Biz\Course\Service\CourseShareService;

class CourseShareServiceImpl extends BaseService implements CourseShareService
{
    public function tryShare($share)
    {
        $user = $this->getCurrentUser();
        if (!$user->isLogin()) {
            throw $this->createAccessDeniedException("未登陆");
        }

        $course = $this->getCourseService()->getCourse($share['courseId']);
        if (empty($course)) {
            throw $this->createNotFoundException("该课程不存在");
        }

        $member = $this->getMemberService()->getCourseMember($share['courseId'], $user['id']);
        if (empty($member) || $member['role'] != 'teacher') {
            throw $this->createAccessDeniedException("非该课程教师");
        }
    }

    public function createCourseShare($share)
    {
        if (!ArrayToolkit::requireds($share, array('courseId', 'toUserId'))) {
            throw $this->createInvalidArgumentException('Lack of required fields');
        }

        $this->tryShare($share);

        $share = ArrayToolkit::parts($share, array(
            'courseId',
            'toUserId',
        ));
        $user = $this->getCurrentUser();
        $share['fromUserId'] = $user['id'];

        $created = $this->getShareDao()->create($share);

        return $created;
    }

    public function findCourseSharesByFromUserId($fromUserId)
    {
        return $this->getShareDao()->findByFromUserId($fromUserId);
    }

    public function findCourseSharesByToUserId($toUserId)
    {
        return $this->getShareDao()->findByToUserId($toUserId);
    }

    public function findCourseSharesByCourseId($courseId)
    {
        return $this->getShareDao()->findByCourseId($courseId);
    }

    public function getCourseShare($id)
    {
        return $this->getShareDao()->get($id);
    }

    public function deleteCourseShare($id)
    {
        $share = $this->getCourseShare($id);
        $user = $this->getCurrentUser();

        if (empty($share)) {
            return ;
        }

        if ($user['id'] != $share['fromUserId']) {
            throw $this->createAccessDeniedException("share#{$id} Not permission to operate");
        }

        $this->getShareDao()->delete($id);
    }

    public function countShareByTimeRangeAndOrgCodeGroupType($startTime, $endTime, $orgCode)
    {
        return $this->getShareDao()->countByTimeRangeAndOrgCodeGroupType($startTime, $endTime, $orgCode);
    }

    protected function getShareDao()
    {
        return $this->createDao('CustomBundle:Course:ShareDao');
    }

    protected function getMemberService()
    {
        return $this->createService('CustomBundle:Course:MemberService');
    }

    protected function getCourseService()
    {
        return $this->createService('CustomBundle:Course:CourseService');
    }

    protected function getUserService()
    {
        return $this->createService('CustomBundle:User:UserService');
    }
}
