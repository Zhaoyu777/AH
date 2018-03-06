<?php

namespace AppBundle\Controller\Classroom;

use AppBundle\Common\ClassroomToolkit;
use AppBundle\Common\Paginator;
use AppBundle\Common\ArrayToolkit;
use Biz\Taxonomy\Service\TagService;
use Biz\Course\Service\CourseService;
use Biz\Course\Service\MemberService;
use Biz\System\Service\SettingService;
use AppBundle\Controller\BaseController;
use Biz\Course\Service\CourseSetService;
use Biz\Classroom\Service\ClassroomService;
use Symfony\Component\HttpFoundation\Request;
use Biz\Classroom\Service\ClassroomReviewService;

class CourseController extends BaseController
{
    public function pickAction($classroomId)
    {
        $this->getClassroomService()->tryManageClassroom($classroomId);

        $conditions = array(
            'status' => 'published',
            'parentId' => 0,
        );

        $activeCourses = $this->getClassroomService()->findActiveCoursesByClassroomId($classroomId);
        if (!empty($activeCourses)) {
            $conditions['excludeIds'] = ArrayToolkit::column($activeCourses, 'parentCourseSetId');
        }

        $user = $this->getCurrentUser();
        if (!$user->isAdmin() && !$user->isSuperAdmin()) {
            $conditions['creator'] = $user['id'];
        }
        $paginator = new Paginator(
            $this->get('request'),
            $this->getCourseSetService()->countCourseSets($conditions),
            5
        );

        $courseSets = $this->searchCourseSetWithCourses(
            $conditions,
            array('updatedTime' => 'DESC'),
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        $users = $this->getUsers($courseSets);

        return $this->render(
            'classroom-manage/course/course-pick-modal.html.twig',
            array(
                'users' => $users,
                'courseSets' => $courseSets,
                'classroomId' => $classroomId,
                'paginator' => $paginator,
            )
        );
    }

    public function listAction(Request $request, $classroomId)
    {
        $classroom = $this->getClassroomService()->getClassroom($classroomId);
        $previewAs = '';

        if (empty($classroom)) {
            throw $this->createNotFoundException();
        }

        $courses = $this->getClassroomService()->findActiveCoursesByClassroomId($classroomId);

        $currentUser = $this->getCurrentUser();
        $courseMembers = array();
        $teachers = array();

        foreach ($courses as &$course) {
            $courseMembers[$course['id']] = $this->getCourseMemberService()->getCourseMember(
                $course['id'],
                $currentUser['id']
            );

            $course['teachers'] = empty($course['teacherIds']) ? array() : $this->getUserService()->findUsersByIds(
                $course['teacherIds']
            );
            $teachers[$course['id']] = $course['teachers'];
            if ($course['isFree']) {
                $course['originPrice'] = '0.00';
            }
        }

        $user = $this->getCurrentUser();

        $member = $user['id'] ? $this->getClassroomService()->getClassroomMember($classroom['id'], $user['id']) : null;
        if (!$this->getClassroomService()->canLookClassroom($classroom['id'])) {
            $classroomName = $this->setting('classroom.name', '班级');

            return $this->createMessageResponse(
                'info',
                "非常抱歉，您无权限访问该{$classroomName}，如有需要请联系客服",
                '',
                3,
                $this->generateUrl('homepage')
            );
        }

        $canManageClassroom = $this->getClassroomService()->canManageClassroom($classroomId);
        if ($request->query->get('previewAs') && $canManageClassroom) {
            $previewAs = $request->query->get('previewAs');
        }

        $member = $this->previewAsMember($previewAs, $member, $classroom);

        $layout = 'classroom/layout.html.twig';
        $isCourseMember = false;
        if ($member && !$member['locked']) {
            $isCourseMember = true;
            $layout = 'classroom/join-layout.html.twig';
        }
        if (!$classroom) {
            $classroomDescription = array();
        } else {
            $classroomDescription = $classroom['about'];
            $classroomDescription = strip_tags($classroomDescription, '');
            $classroomDescription = preg_replace('/ /', '', $classroomDescription);
        }

        return $this->render(
            'classroom/course/list.html.twig',
            array(
                'classroom' => $classroom,
                'member' => $member,
                'teachers' => $teachers,
                'courses' => $courses,
                'courseMembers' => $courseMembers,
                'layout' => $layout,
                'classroomDescription' => $classroomDescription,
                'isCourseMember' => $isCourseMember,
            )
        );
    }

    public function searchAction(Request $request, $classroomId)
    {
        $this->getClassroomService()->tryManageClassroom($classroomId);
        $key = $request->request->get('key');

        $activeCourses = $this->getClassroomService()->findActiveCoursesByClassroomId($classroomId);
        $excludeIds = ArrayToolkit::column($activeCourses, 'parentCourseSetId');

        $conditions = array('title' => "%{$key}%");
        $conditions['status'] = 'published';
        $conditions['parentId'] = 0;
        $conditions['excludeIds'] = $excludeIds;

        $user = $this->getCurrentUser();
        if (!$user->isAdmin() && !$user->isSuperAdmin()) {
            $conditions['creator'] = $user['id'];
        }

        $paginator = new Paginator(
            $this->get('request'),
            $this->getCourseSetService()->countCourseSets($conditions),
            5
        );

        $courseSets = $this->searchCourseSetWithCourses(
            $conditions,
            array('updatedTime' => 'DESC'),
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        $users = $this->getUsers($courseSets);

        return $this->render(
            'course/course-select-list.html.twig',
            array(
                'users' => $users,
                'courseSets' => $courseSets,
                'paginator' => $paginator,
                'classroomId' => $classroomId,
                'type' => 'ajax_pagination',
            )
        );
    }

    protected function getUsers($courseSets)
    {
        $userIds = array();
        foreach ($courseSets as &$courseSet) {
            // $tags = $this->getTagService()->findTagsByOwner(array('ownerType' => 'course', 'ownerId' => $course['id']));
            if (!empty($courseSet['tags'])) {
                $tags = $this->getTagService()->findTagsByIds($courseSet['tags']);

                $courseSet['tags'] = ArrayToolkit::column($tags, 'id');
            }
            $userIds = array_merge($userIds, array($courseSet['creator']));
        }

        $users = $this->getUserService()->findUsersByIds($userIds);
        if (!empty($users)) {
            $users = ArrayToolkit::index($users, 'id');
        }

        return $users;
    }

    /**
     * @param string $previewAs
     * @param array  $member
     * @param array  $classroom
     *
     * @return array
     */
    private function previewAsMember($previewAs, $member, $classroom)
    {
        $user = $this->getCurrentUser();

        if (in_array($previewAs, array('guest', 'auditor', 'member'), true)) {
            if ($previewAs === 'guest') {
                return array();
            }

            $deadline = ClassroomToolkit::buildMemberDeadline(array(
                'expiryMode' => $classroom['expiryMode'],
                'expiryValue' => $classroom['expiryValue'],
            ));

            $member = array(
                'id' => 0,
                'classroomId' => $classroom['id'],
                'userId' => $user['id'],
                'orderId' => 0,
                'levelId' => 0,
                'noteNum' => 0,
                'threadNum' => 0,
                'remark' => '',
                'role' => array('auditor'),
                'locked' => 0,
                'createdTime' => 0,
                'deadline' => $deadline,
            );

            if ($previewAs === 'member') {
                $member['role'] = array('member');
            }
        }

        return $member;
    }

    private function searchCourseSetWithCourses($conditions, $orderbys, $start, $limit)
    {
        $courseSets = $this->getCourseSetService()->searchCourseSets($conditions, $orderbys, $start, $limit);

        if (empty($courseSets)) {
            return array();
        }

        $courseSets = ArrayToolkit::index($courseSets, 'id');
        $courses = $this->getCourseService()->findCoursesByCourseSetIds(array_keys($courseSets));
        if (!empty($courses)) {
            foreach ($courses as $course) {
                if ($course['status'] != 'published') {
                    continue;
                }

                if (empty($courseSets[$course['courseSetId']]['courses'])) {
                    $courseSets[$course['courseSetId']]['courses'] = array($course);
                } else {
                    $courseSets[$course['courseSetId']]['courses'][] = $course;
                }
            }
        }

        return array_values($courseSets);
    }

    /**
     * @return CourseService
     */
    private function getCourseService()
    {
        return $this->createService('Course:CourseService');
    }

    /**
     * @return CourseSetService
     */
    protected function getCourseSetService()
    {
        return $this->createService('Course:CourseSetService');
    }

    /**
     * @return ClassroomService
     */
    private function getClassroomService()
    {
        return $this->createService('Classroom:ClassroomService');
    }

    /**
     * @return ClassroomReviewService
     */
    protected function getClassroomReviewService()
    {
        return $this->createService('Classroom:ClassroomReviewService');
    }

    /**
     * @return TagService
     */
    private function getTagService()
    {
        return $this->createService('Taxonomy:TagService');
    }

    /**
     * @return SettingService
     */
    protected function getSettingService()
    {
        return $this->createService('System:SettingService');
    }

    /**
     * @return MemberService
     */
    protected function getCourseMemberService()
    {
        return $this->createService('Course:MemberService');
    }
}
