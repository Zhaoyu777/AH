<?php

namespace CustomBundle\Controller\Course;

use AppBundle\Common\ArrayToolkit;
use AppBundle\Controller\BaseController;
use Symfony\Component\HttpFoundation\Request;

class StudentManageController extends BaseController
{
    public function studentsAction(Request $request, $courseId)
    {
        $course = $this->getCourseService()->tryManageCourse($courseId);
        if ($course['status'] == 'delete') {
            return $this->createMessageResponse('info', '该课程已删除', null, 3000, $this->generateUrl('my_teaching_instant_courses'));
        }
        $courseSet = $this->getCourseSetService()->getCourseSet($course['courseSetId']);

        $groups = $this->getCourseGroupService()->findCourseGroupsByCourseIdWithMembers($courseId, true);
        $groups = ArrayToolkit::index($groups, 'id');
        $defaultGroup = $this->getCourseGroupService()->getDefaultGroupByCourseId($courseId);
        $scores = $this->getScoreService()->findUserSumScoresByCourseId($courseId);

        return $this->render('prepare-course-manage/student-manage/students.html.twig', array(
           'course' => $course,
           'groups' => $groups,
           'courseSet' => $courseSet,
           'scores' => $scores,
           'defaultGroup' => $defaultGroup,
        ));
    }

    public function sortAction(Request $request, $courseId)
    {
        $ids = $request->request->get('ids');

        $this->getGroupMemberService()->sortCourseGroupMembers($courseId, $ids);

        return $this->createJsonResponse(true);
    }

    public function createCourseStudentAction(Request $request, $courseId)
    {
        if ($request->getMethod() == 'POST') {
            $studentIds = $request->request->get('ids');

            $this->getMemberService()->batchBecomeStudents($courseId, $studentIds);

            return $this->createJsonResponse(true);
        }
        $course = $this->getCourseService()->tryManageCourse($courseId);

        return $this->render('prepare-course-manage/student-manage/add-modal.html.twig', array(
            'course' => $course,
            'userIds' => array()
        ));
    }

    public function studentsMatchAction(Request $request, $courseId)
    {
        $queryField = $request->query->get('q');
        if (empty($queryField)) {
            return $this->createJsonResponse(array());
        }
        $excludeIds = $request->query->get('excludeIds', array());
        $userIds = $this->getMemberService()->findMemberUserIdsByCourseId($courseId);
        $conditions = array(
            'queryField' => $queryField,
            'excludeIds' => array_merge($userIds, $excludeIds),
        );

        $users = $this->getUserService()->searchAllUsers(
            $conditions,
            array('createdTime' => 'DESC'),
            0,
            100
        );

        $userIds = ArrayToolkit::column($users, 'id');
        $userProfiles = $this->getUserService()->findUserProfilesByIds($userIds);

        $students = array();

        foreach ($users as $user) {
            $avatar = $this->get('web.twig.app_extension')->userAvatar($user, 'small');
            $students[] = array(
                'id' => $user['id'],
                'truename' => $user['truename'],
                'nickname' => $user['number'],
                'role' => in_array('ROLE_TEACHER', $user['roles']) ? '教师' : '学员',
                'avatar' => $this->getWebExtension()->getFilePath($avatar, 'avatar.png'),
                'isVisible' => 1,
            );
        }

        return $this->createJsonResponse($students);
    }

    public function courseMemberMatchAction(Request $request, $courseId)
    {
        $queryField = $request->query->get('q');
        if (empty($queryField)) {
            return $this->createJsonResponse(array());
        }
        $excludeMemberIds = $request->query->get('excludeIds', array());
        $excludeMembers = $this->getMemberService()->findMembersByIdsWithUserInfo($excludeMemberIds);
        $excludeIds = ArrayToolkit::column($excludeMembers, 'userId');

        $members = $this->getMemberService()->findCourseStudents($courseId, 0, PHP_INT_MAX);
        $members = ArrayToolkit::index($members, 'userId');
        $userIds = ArrayToolkit::column($members, 'userId');

        if (empty($userIds)) {
            $users = array();
        } else {
            $conditions = array(
                'queryField' => $queryField,
                'userIds' => $userIds,
            );
            if (!empty($excludeIds)) {
                $conditions['excludeIds'] = $excludeIds;
            }
            $users = $this->getUserService()->searchAllUsers(
                $conditions,
                array('createdTime' => 'DESC'),
                0,
                100
            );
        }

        $userIds = ArrayToolkit::column($users, 'id');

        $students = array();

        foreach ($users as $user) {
            $avatar = $this->get('web.twig.app_extension')->userAvatar($user, 'small');
            $students[] = array(
                'id' => $members[$user['id']]['id'],
                'truename' => $user['truename'],
                'nickname' => $user['number'],
                'role' => in_array('ROLE_TEACHER', $user['roles']) ? '教师' : '学员',
                'avatar' => $this->getWebExtension()->getFilePath($avatar, 'avatar.png'),
                'isVisible' => 1,
            );
        }

        return $this->createJsonResponse($students);
    }

    public function createGroupAction(Request $request, $courseId)
    {
        if ($request->getMethod() == 'POST') {
            $fields = $request->request->all();
            $fields['courseId'] = $courseId;

            $group = $this->getCourseGroupService()->createCourseGroup($fields);

            return $this->createJsonResponse(true);
        }
        $students = $this->getMemberService()->findCourseStudents($courseId, 0, PHP_INT_MAX);
        $studentIds = ArrayToolkit::column($students, 'id');

        return $this->render('prepare-course-manage/group-manage/create-modal.html.twig', array(
            'courseId' => $courseId,
            'studentIds' => $studentIds
        ));
    }

    public function removeGroupMemberAction($groupMemberId)
    {
        $this->getGroupMemberService()->deleteGroupMember($groupMemberId);

        return $this->createJsonResponse(true);
    }

    public function deleteGroupAction($groupId)
    {
        $this->getCourseGroupService()->deleteGroup($groupId);

        return $this->createJsonResponse(true);
    }

    public function removeCourseStudentAction($courseSetId, $courseId, $userId)
    {
        $this->getCourseService()->tryManageCourse($courseId, $courseSetId);
        $user = $this->getCurrentUser();

        $condition = array(
            'targetType' => 'course',
            'targetId' => $courseId,
            'userId' => $userId,
            'status' => 'paid',
        );
        $orders = $this->getOrderService()->searchOrders($condition, array('created_time' => 'DESC'), 0, 1);
        if (!empty($orders)) {
            $order = array_shift($orders);
            $reason = array(
                'type' => 'other',
                'note' => '"'.$user['nickname'].'"'.' 手动移除',
                'operator' => $user['id'],
            );
            $this->getOrderService()->applyRefundOrder($order['id'], null, $reason);
        }
        $this->getCourseMemberService()->removeCourseStudent($courseId, $userId);

        return $this->createJsonResponse(array('success' => true));
    }

    protected function getCourseService()
    {
        return $this->createService('CustomBundle:Course:CourseService');
    }

    protected function getMemberService()
    {
        return $this->createService('CustomBundle:Course:MemberService');
    }

    protected function getWebExtension()
    {
        return $this->container->get('web.twig.extension');
    }

    protected function getUserService()
    {
        return $this->createService('User:UserService');
    }

    protected function getCourseGroupService()
    {
        return $this->createService('CustomBundle:Course:CourseGroupService');
    }

    protected function getGroupMemberService()
    {
        return $this->createService('CustomBundle:Course:GroupMemberService');
    }

    protected function getCourseSetService()
    {
        return $this->createService('CustomBundle:Course:CourseSetService');
    }

    protected function getScoreService()
    {
        return $this->createService('CustomBundle:Score:ScoreService');
    }

    protected function getOrderService()
    {
        return $this->createService('Order:OrderService');
    }

    protected function getCourseMemberService()
    {
        return $this->createService('CustomBundle:Course:MemberService');
    }
}
