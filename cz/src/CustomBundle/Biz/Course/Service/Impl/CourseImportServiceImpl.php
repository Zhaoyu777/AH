<?php

namespace CustomBundle\Biz\Course\Service\Impl;

use Biz\BaseService;
use AppBundle\Common\ArrayToolkit;
use Codeages\Biz\Framework\Event\Event;
use CustomBundle\Biz\Course\Service\CourseImportService;
use CustomBundle\Biz\Course\Copy\CourseLessonCopy;

class CourseImportServiceImpl extends BaseService implements CourseImportService
{
    public function tryImport($courseId)
    {
        $user = $this->getCurrentUser();
        if (!$user->isLogin()) {
            throw $this->createAccessDeniedException("未登陆");
        }

        if (!$this->getMemberService()->isCourseTeacher($courseId, $user['id'])) {
            throw $this->createAccessDeniedException("没有操作权限");
        }
    }

    public function importCourse($fromCourseId, $toCourseId)
    {
        $this->tryImport($toCourseId);

        $fromLessons = $this->getCourseLessonService()->findCourseLessonsByCourseId($fromCourseId);
        $fromLessons = ArrayToolkit::index($fromLessons, 'number');

        $toLessons = $this->getCourseLessonService()->findCourseLessonsByCourseIdAndStatus($toCourseId, 'created');
        $toLessons = ArrayToolkit::index($toLessons, 'number');
        $lessons = array();

        $this->beginTransaction();
        try {
            foreach ($toLessons as $key => $toLesson) {
                $toChapters = $this->getCourseLessonService()->findCourseChaptersByLessonId($toLesson['id']);
                if (!empty($toChapters)) {
                    continue;
                }

                if (!empty($fromLessons[$key])) {
                    $lessons[] = $this->importCourseLesson($fromLessons[$key]['id'], $toLesson['id']);
                }
            }

            $this->commit();
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
        if (empty($lessons)) {
            throw $this->createAccessDeniedException("没有可以导入的课次内容");
        }

        return $lessons;
    }

    public function importCourseLesson($fromLessonId, $toLessonId)
    {
        $user = $this->getCurrentUser();
        if (!$user->isLogin()) {
            throw $this->createAccessDeniedException("未登陆");
        }

        $toLesson = $this->getCourseLessonService()->getCourseLesson($toLessonId);

        if ($toLesson['status'] == 'teached') {
            throw $this->createAccessDeniedException("课次已完成，不能导入");
        }

        if ($toLesson['status'] == 'teaching') {
            throw $this->createAccessDeniedException("课次正在进行中，不能导入");
        }

        $chapters = $this->getCourseLessonService()->findCourseChaptersByLessonId($toLessonId);
        if (!empty($chapters)) {
            throw $this->createAccessDeniedException("课次任务未清空，不能导入");
        }

        $this->tryImport($toLesson['courseId']);

        $this->beginTransaction();
        try {
            $courseLessonCopy = new CourseLessonCopy();
            $lesson = $courseLessonCopy->copy($fromLessonId, $toLessonId);

            $this->commit();
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }

        return $lesson;
    }

    protected function getCourseLessonService()
    {
        return $this->createService('CustomBundle:Course:CourseLessonService');
    }

    protected function getTaskService()
    {
        return $this->createService('CustomBundle:Task:TaskService');
    }

    protected function getCourseService()
    {
        return $this->createService('CustomBundle:Course:CourseService');
    }

    protected function getActivityService()
    {
        return $this->createService('Activity:ActivityService');
    }

    protected function getMemberService()
    {
        return $this->createService('CustomBundle:Course:MemberService');
    }

    protected function getCourseChapterDao()
    {
        return $this->createDao('CustomBundle:Course:CourseChapterDao');
    }
}
