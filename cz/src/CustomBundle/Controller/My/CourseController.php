<?php

namespace CustomBundle\Controller\My;

use AppBundle\Common\Paginator;
use AppBundle\Common\ArrayToolkit;
use AppBundle\Controller\BaseController;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Controller\My\CourseController as BaseCourseController;

class CourseController extends BaseCourseController
{
    public function indexAction()
    {
        if ($this->getCurrentUser()->isTeacher()) {
            return $this->redirect($this->generateUrl('my_teaching_instant_courses'));
        } else {
            return $this->redirect($this->generateUrl('my_instant_courses_learning'));
        }
    }

    public function instantLearningAction(Request $request)
    {
        $user = $this->getCurrentUser();

        return $this->render(
            'my/learning/course/instant-learning.html.twig'
        );
    }

    public function loadInstantLearningAction(Request $request)
    {
        $user = $this->getCurrentUser();

        $termCode = $request->request->get('termCode');

        return $this->render(
            'my/learning/course/instant-learning-tr.html.twig',
            $this->instantLearning($request, $user['id'], $termCode)
        );
    }

    private function instantLearning($request, $userId, $termCode)
    {
        $members = $this->getCourseMemberService()->findStudentMemberByUserId($userId);
        $courseIds = ArrayToolkit::column($members, 'courseId');

        if (empty($courseIds)) {
            $paginator = new Paginator(
                $request,
                0,
                20
            );
            return array(
                'courses' => array(),
                'lessons' => array(),
                'courseSets' => array(),
                'termCode' => $termCode,
                'paginator' => $paginator,
            );
        }

        $conditions = array(
            'courseIds' => $courseIds,
            'type' => 'instant',
            'status' => 'published',
            'termCode' => $termCode,
        );

        $courses = $this->getCourseService()->searchCourses(
            $conditions,
            array('courseSetId' => 'ASC'),
            0,
            1000
        );

        $courseIds = ArrayToolkit::column($courses, 'id');
        $lessons = $this->getCourseLessonService()->findCourseLessonsByCourseIds($courseIds);
        $lessons = ArrayToolkit::group($lessons, 'courseId');

        $courseSetIds = ArrayToolkit::column($courses, 'courseSetId');
        $courseSets = $this->getCourseSetService()->findCourseSetsByIds($courseSetIds);

        $lastlessons = $this->getCourseLessonService()->findLastTeachCourseLessonsByCourseIds($courseIds);
        $nextlessons = $this->getCourseLessonService()->findNextTeachCourseLessonsByCourseIds($courseIds);

        $teachers = $this->getTeachers($courseSets);

        return array(
            'courses' => $courses,
            'courseSets' => $courseSets,
            'termCode' => $termCode,
            'lessons' => $lessons,
            'lastlessons' => $lastlessons,
            'nextlessons' => $nextlessons,
            'teachers' => $teachers,
        );
    }

    private function getTeachers($courseSets)
    {
        if (empty($courseSets)) {
            return array();
        }
        $ids = array();
        $teacherIds = ArrayToolkit::column($courseSets, 'teacherIds');
        foreach ($teacherIds as $key => $teacherId) {
            $ids[$key] = $teacherId[0];
        }

        $teachers = $this->getUserService()->findUsersByIds($ids);

        return $teachers;
    }

    protected function getCourseSetService()
    {
        return $this->createService('CustomBundle:Course:CourseSetService');
    }

    protected function getCourseMemberService()
    {
        return $this->createService('CustomBundle:Course:MemberService');
    }

    protected function getCourseService()
    {
        return $this->createService('CustomBundle:Course:CourseService');
    }

    protected function getCourseLessonService()
    {
        return $this->createService('CustomBundle:Course:CourseLessonService');
    }

    protected function getUserService()
    {
        return $this->createService('User:UserService');
    }
}
