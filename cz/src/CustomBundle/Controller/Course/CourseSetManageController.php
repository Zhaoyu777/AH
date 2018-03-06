<?php

namespace CustomBundle\Controller\Course;

use AppBundle\Common\Paginator;
use AppBundle\Controller\BaseController;
use AppBundle\Common\ArrayToolkit;
use Symfony\Component\HttpFoundation\Request;

class CourseSetManageController extends BaseController
{
    public function headerAction($courseSet, $termCode)
    {
        $term = $this->getCourseService()->getTermByShortCode($termCode);

        return $this->render('courseset-manage/instant-header.html.twig', array(
            'courseSet' => $courseSet,
            'term' => $term,
        ));
    }

    public function sidebarAction($sideNav, $courseSetId, $termCode)
    {
        $user = $this->getCurrentUser();

        $courseCount = $this->getCourseMemberService()->countCourseSetTeachers($courseSetId, $user['id']);

        return $this->render('courseset-manage/instant-sidebar.html.twig', array(
            'side_nav' => $sideNav,
            'courseSetId' => $courseSetId,
            'termCode' => $termCode,
            'courseCount' => $courseCount,
        ));
    }

    public function coursesAction(Request $request, $courseSetId)
    {

        $user = $this->getCurrentUser();

        $courseTeachers = $this->getCourseMemberService()->findCourseSetTeachers($courseSetId, $user['id']);
        $courseIds = ArrayToolkit::column($courseTeachers, 'courseId');
        $courses = $this->getCourseService()->findCoursesByIds($courseIds);

        $conditions = array(
            'courseIds' => $courseIds,
            'role' => 'student'
        );

        $courseMemberCounts = $this->getCourseMemberService()->searchMemberCountGroupByFields(
            $conditions,
            'courseId',
            0,
            PHP_INT_MAX
        );
        $courseMemberCounts = ArrayToolkit::index($courseMemberCounts, 'courseId');
        $courseSet = $this->getCourseSetService()->getCourseSet($courseSetId);

        return $this->render('courseset-manage/courses/index.html.twig', array(
            'courses' => $courses,
            'courseSet' => $courseSet,
            'courseMemberCounts' => $courseMemberCounts,
        ));
    }

    public function teacherScoreShowAction(Request $request, $courseSetId)
    {
        $user = $this->getCurrentUser();
        $courseSet = $this->getCourseSetService()->getCourseSet($courseSetId);
        $courses = $this->getCourseService()->findCoursesByCourseSetId($courseSetId);
        $courseIds = ArrayToolkit::column($courses, 'id');

        $conditions = array(
            'courseIds' => $courseIds,
            'unUserId' => 0,
        );

        $perCount = 20;

        $paginator = new Paginator(
            $request,
            $this->getTeacherScoreService()->countTeacherScores($conditions),
            $perCount
        );

        $scores = $this->getTeacherScoreService()->searchTeacherScores(
            $conditions,
            array('createdTime' => 'DESC'),
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        $userIds = ArrayToolkit::column($scores, 'userId');
        $users = $this->getUserService()->findUsersByIds($userIds);
        $users = ArrayToolkit::index($users, 'id');

        $lessonIds = ArrayToolkit::column($scores, 'lessonId');
        $lessons = $this->getCourseLessonService()->findCourseLessonsByIds($lessonIds);
        $lessons = ArrayToolkit::index($lessons, 'id');

        $creditScore = $this->getTeacherScoreService()->getSumScoreByCourseSetId($courseSetId);

        return $this->render('courseset-manage/teacher-score/index.html.twig', array(
            'creditScore' => $creditScore,
            'scores' => $this->scoresCheck($scores),
            'paginator' => $paginator,
            'courseSet' => $courseSet,
            'users' => $users,
            'lessons' => $this->lessonsCheck($lessons),
        ));
    }

    protected function lessonsCheck($lessons)
    {
        foreach ($lessons as $key => &$lesson) {
            $lesson['title'] = empty($lesson['title']) ? '课次'.$lesson['number'] : $lesson['title'];
        }

        return $lessons;
    }

    protected function scoresCheck($scores)
    {
        $types = array(
            'in' => '创建课中任务',
            'before' => '创建课前任务',
            'after' => '创建课后任务',
            'signIn' => '完成签到',
        );

        foreach ($scores as &$score) {
            $score['description'] = $types[$score['source']];
        }

        return $scores;
    }

    protected function getCourseMemberService()
    {
        return $this->createService('CustomBundle:Course:MemberService');
    }

    protected function getCourseService()
    {
        return $this->createService('CustomBundle:Course:CourseService');
    }

    protected function getCourseSetService()
    {
        return $this->createService('Course:CourseSetService');
    }

    protected function getTagService()
    {
        return $this->createService('Taxonomy:TagService');
    }

    protected function getCourseLessonService()
    {
        return $this->createService('CustomBundle:Course:CourseLessonService');
    }

    protected function getTeacherScoreService()
    {
        return $this->getBiz()->service('CustomBundle:Score:TeacherScoreService');
    }
}
