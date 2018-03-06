<?php

namespace CustomBundle\Controller\Course;

use AppBundle\Controller\BaseController;
use AppBundle\Common\ArrayToolkit;
use Symfony\Component\HttpFoundation\Request;

class CourseShareController extends BaseController
{
    public function indexAction($courseId)
    {
        $user = $this->getCurrentUser();

        $course = $this->getCourseService()->tryManageCourse($courseId);

        $courseShares = $this->getCourseShareService()->findCourseSharesByCourseId($courseId);
        $courseShares = ArrayToolkit::index($courseShares, 'toUserId');

        $userIds = ArrayToolkit::column($courseShares, 'toUserId');
        $users = $this->getUserService()->findUsersByIds($userIds);

        return $this->render('prepare-course-manage/share/index.html.twig', array(
            'course' => $course,
            'courseShares' => $courseShares,
            'users' => $users,
        ));
    }

    public function createAction(Request $request, $courseId)
    {
        $request = $request->query->all();
        $user = $this->getCurrentUser();

        $share = array(
            'courseId' => $courseId,
            'toUserId' => $request['toUserId'],
            'fromUserId' => $user['id']
        );

        $shareCourse = $this->getCourseShareService()->createCourseShare($share);

        return $this->createJsonResponse($shareCourse);
    }

    public function deleteAction($shareId)
    {
        $courseShares = $this->getCourseShareService()->deleteCourseShare($shareId);

        return $this->createJsonResponse(true);
    }

    public function shareTeachersMatchAction(Request $request, $courseId)
    {
        $queryField = $request->query->get('q');

        $teachers = $this->getCourseShareService()->findCourseSharesByCourseId($courseId);
        $teacherIds = ArrayToolkit::column($teachers, 'toUserId');

        $users = $this->getUserService()->searchAllUsers(
            array('queryField' => $queryField, 'roles' => 'ROLE_TEACHER', 'excludeIds' => $teacherIds),
            array('createdTime' => 'DESC'),
            0,
            10
        );

        $teachers = array();

        foreach ($users as $user) {
            $avatar = $this->get('web.twig.app_extension')->userAvatar($user, 'small');
            $teachers[] = array(
                'id' => $user['id'],
                'truename' => $user['truename'],
                'nickname' => $user['number'],
                'role' => '教师',
                'avatar' => $this->getWebExtension()->getFilePath($avatar, 'avatar.png'),
                'isVisible' => 1,
            );
        }

        return $this->createJsonResponse($teachers);
    }

    protected function getCourseService()
    {
        return $this->createService('CustomBundle:Course:CourseService');
    }

    protected function getCourseSetService()
    {
        return $this->createService('Course:CourseSetService');
    }

    protected function getCourseShareService()
    {
        return $this->createService('CustomBundle:Course:CourseShareService');
    }
}
