<?php

namespace CustomBundle\Biz\Course\Copy;

use AppBundle\Common\CurlToolkit;
use AppBundle\Common\ArrayToolkit;
use Topxia\Service\Common\ServiceKernel;
use Symfony\Component\HttpFoundation\Request;

class ChapterCopy extends AbstractCopy
{
    public function copy($fromLessonId, $toLessonId)
    {
        $toLesson = $this->getCourseLessonService()->getCourseLesson($toLessonId);

        $courseChapters = $this->getCourseLessonService()->findChaptersByLessonIdAndTpye($fromLessonId, 'chapter');
        $chapters = $this->getCourseLessonService()->findChaptersByLessonId($fromLessonId);

        $chapters = ArrayToolkit::index($chapters, 'categoryId');

        $result = array();
        foreach ($courseChapters as $key => $formChapter) {
            $chapter = array(
                'type' => 'chapter',
                'courseId' => $toLesson['courseId'],
                'lessonId' => $toLessonId,
                'seq' => $formChapter['seq'],
                'stage' => $formChapter['stage'],
                'title' => $formChapter['title'],

            );

            $chapter = $this->getCourseService()->createChapter($chapter);
            $chapter['categoryId'] = $chapter['id'];
            $chapter['number'] = $chapters[$formChapter['id']]['number'];
            $result[] = $this->getCourseLessonService()->createChapter($chapter);
        }

        return $result;
    }

    protected function getCourseService()
    {
        return $this->getServiceKernel()->createService('Course:CourseService');
    }

    protected function getCourseLessonService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:Course:CourseLessonService');
    }

    protected function getServiceKernel()
    {
        return ServiceKernel::instance();
    }
}
