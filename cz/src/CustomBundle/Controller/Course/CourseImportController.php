<?php

namespace CustomBundle\Controller\Course;

use AppBundle\Controller\BaseController;
use AppBundle\Common\ArrayToolkit;
use Symfony\Component\HttpFoundation\Request;

class CourseImportController extends BaseController
{
    public function importCourseLessonIndexAction($lessonId)
    {
        $user = $this->getCurrentUser();

        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonId);

        return $this->render('prepare-course-manage/import-manage/lesson-index.html.twig', array(
            'lesson' => $lesson,
        ));
    }

    public function importCourseIndexAction($courseId)
    {
        $user = $this->getCurrentUser();

        $course = $this->getCourseService()->getCourse($courseId);

        return $this->render('prepare-course-manage/import-manage/course-index.html.twig', array(
            'course' => $course,
        ));
    }

    public function importCourseAction(Request $request)
    {
        $user = $this->getCurrentUser();

        $fromCourseId = $request->query->get('fromCourseId');
        $toCourseId = $request->query->get('toCourseId');

        $this->getCourseImportService()->importCourse($fromCourseId, $toCourseId);

        $url = $this->generateUrl('custom_course_lesson_list', array('courseId'=>$toCourseId), true);

        return $this->createJsonResponse($url);
    }

    public function importCourseLessonAction(Request $request)
    {
        $user = $this->getCurrentUser();

        $fromLessonId = $request->query->get('fromLessonId');
        $toLessonId = $request->query->get('toLessonId');
        $lesson = $this->getCourseLessonService()->getCourseLesson($toLessonId);

        $this->getCourseImportService()->importCourseLesson($fromLessonId, $toLessonId);

        $url = $this->generateUrl('custom_lesson_tasks', array('lessonId'=>$lesson['id']), true);

        return $this->createJsonResponse($url);
    }

    public function importCourseWarnAction(Request $request)
    {
        $fromCourseId = $request->query->get('fromCourseId');
        $toCourseId = $request->query->get('toCourseId');

        $lessons =$this->getCourseLessonService()->findLessonsByFromCourseIdAndtoCourseId($fromCourseId, $toCourseId);

        $warnStr = "";
        foreach ($lessons as $lesson) {
            $warnStr .= "课次{$lesson['number']},";
        }

        return $this->createJsonResponse(substr($warnStr, 0, strlen($warnStr)-1));
    }

    protected function sortCourseLessons($courseId)
    {
        $lessons =$this->getCourseLessonService()->findCourseLessonsByCourseId($courseId);

        $result = array();
        foreach ($lessons as $key => $lesson) {
            $result[] = array(
                'id' => $lesson['id'],
                'count' => "课次{$lesson['number']} {$lesson['title']}"
            );
        }

        return $result;
    }

    protected function getCourseService()
    {
        return $this->createService('CustomBundle:Course:CourseService');
    }

    protected function getCourseSetService()
    {
        return $this->createService('Course:CourseSetService');
    }

    protected function getCourseLessonService()
    {
        return $this->createService('CustomBundle:Course:CourseLessonService');
    }

    protected function getTaskService()
    {
        return $this->createService('Task:TaskService');
    }

    protected function getCourseShareService()
    {
        return $this->createService('CustomBundle:Course:CourseShareService');
    }

    protected function getCourseImportService()
    {
        return $this->createService('CustomBundle:Course:CourseImportService');
    }

    protected function getCourseMemberService()
    {
        return $this->createService('CustomBundle:Course:MemberService');
    }
}
