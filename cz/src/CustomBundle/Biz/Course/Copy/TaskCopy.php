<?php

namespace CustomBundle\Biz\Course\Copy;

use AppBundle\Common\CurlToolkit;
use AppBundle\Common\ArrayToolkit;
use Topxia\Service\Common\ServiceKernel;
use Symfony\Component\HttpFoundation\Request;

class TaskCopy extends AbstractCopy
{
    private $fileTasks = array('video', 'audio', 'interval', 'ppt', 'doc');

    private $fileType = array('download', 'practice');

    public function copy($fromTaskId, $toLessonId)
    {
        $toLesson = $this->getCourseLessonService()->getCourseLesson($toLessonId);
        $tocourse = $this->getCourseService()->getCourse($toLesson['courseId']);

        $fromTask = $this->getTaskService()->getTask($fromTaskId);
        $activityFiled = $this->getActivityService()->getActivity($fromTask['activityId']);
        $activityFiled['fromCourseId'] = $tocourse['id'];
        $activityFiled['fromCourseSetId'] = $tocourse['courseSetId'];

        $taskFiled = $fromTask;
        $taskFiled['courseId'] = $tocourse['id'];
        $taskFiled['status'] = 'published';

        $activityConfig = $this->getActivityService()->getActivityConfig($activityFiled['mediaType']);
        $activityType = $activityConfig->get($activityFiled['mediaId']);
        $taskFiled = array_merge($activityType, $activityFiled, $taskFiled);
        if ($taskFiled['type'] == 'testpaper') {
            $taskFiled['mediaId'] = $activityType['mediaId'];
        }
        if ($taskFiled['type'] == 'ppt') {
            $activityType['file'] = $this->getUploadFileService()->getFullFile($activityType['mediaId']);
        }

        if (in_array($taskFiled['type'], $this->fileTasks)) {
            $activityType['id'] = $activityType['mediaId'];
            $taskFiled['ext'] = $activityType;
            $activityType['name'] = $activityType['file']['name'];
            $activityType['source'] = isset($activityType['mediaSource']) ? $activityType['mediaSource'] : 'activitypractice';
            $taskFiled['media'] = json_encode($activityType);
        }

        if (in_array($taskFiled['type'], $this->fileType)) {
            $taskFiled['materials'] = json_encode(array());
            unset($activityType['id']);
            $taskFiled['ext'] = $activityType;
        }

        if ($taskFiled['type'] == 'homework') {
            $items = $this->getTestpaperService()->findItemsByTestId($activityType['id']);
            $taskFiled['finishCondition'] = $activityType['passedCondition'];
            $taskFiled['title'] = $activityType['name'];
            $taskFiled['questionIds'] = ArrayToolkit::column($items, 'questionId');
            unset($activityType['id']);
            $taskFiled['ext'] = $activityType;
        }
        if ($taskFiled['type'] == 'questionnaire') {
            $taskFiled['mediaId'] = $activityType['mediaId'];
        }
        if ($taskFiled['type'] == 'randomTestpaper') {
            $metas = $activityType['metas'];
            $taskFiled = array_merge($taskFiled, $metas);
        }

        $task = $this->getTaskService()->createTask($taskFiled);

        if (in_array($taskFiled['type'], $this->fileType)) {
            $fields = array('materials' => json_encode(array_flip($activityType['fileIds'])));
            $activity = $this->getActivityService()->getActivity($task['activityId']);
            $activityType = $activityConfig->update($activity['mediaId'], $fields, array());
            $materials = $this->getMaterialService()->findMaterialsByLessonId($fromTask['activityId']);
            foreach ($materials as $material) {
                $material['lessonId'] = $task['activityId'];
                $material['courseSetId'] = $tocourse['courseSetId'];
                $material['courseId'] = $tocourse['id'];
                unset($material['id']);
                $this->getMaterialService()->addMaterial($material, true);
            }
        }

        $CourseChapter = $this->getCourseLessonService()->getCourseChapter($fromTask['categoryId']);

        $chapterFiled = array(
            'lessonId' => $toLesson['id'],
            'categoryId' => $task['categoryId'],
            'stage' => $CourseChapter['stage'],
            'seq' => $CourseChapter['seq']
        );
        $this->getCourseService()->updateChapter($tocourse['id'], $task['categoryId'], $chapterFiled);

        return $task;
    }

    protected function getTaskService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:Task:TaskService');
    }

    protected function getCourseService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:Course:CourseService');
    }

    protected function getActivityService()
    {
        return $this->getServiceKernel()->createService('Activity:ActivityService');
    }

    protected function getTestpaperService()
    {
        return $this->getServiceKernel()->createService('Testpaper:TestpaperService');
    }

    protected function getCourseLessonService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:Course:CourseLessonService');
    }

    protected function getQuestionnaireService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:Questionnaire:QuestionnaireService');
    }

    protected function getMaterialService()
    {
         return $this->getServiceKernel()->createService('CustomBundle:Course:MaterialService');
    }

    protected function getServiceKernel()
    {
        return ServiceKernel::instance();
    }

    protected function getUploadFileService()
    {
        return $this->getServiceKernel()->createService('File:UploadFileService');
    }
}
