<?php

namespace CustomBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use AppBundle\Controller\TaskManageController as BaseTaskManageController;
use Codeages\Biz\Framework\Service\Exception\InvalidArgumentException;
use AppBundle\Common\ArrayToolkit;

class TaskManageController extends BaseTaskManageController
{
    public function createAction(Request $request, $lessonId)
    {
        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonId);
        $course = $this->getCourseService()->tryManageCourse($lesson['courseId']);
        if ($request->getMethod() == 'POST') {
            $task = $request->request->all();

            return $this->createTask($request, $task, $lesson);
        }

        $chapterId = $request->query->get('chapterId');
        $stage = $request->query->get('stage');
        $courseSet  = $this->getCourseSetService()->getCourseSet($course['courseSetId']);
        $aims = $this->getTeachingAimService()->findByLessonId($lesson['id']);

        return $this->render('prepare-course-manage/lesson-manage/task-create-modal.html.twig', array(
            'mode'       => 'create',
            'lesson'     => $lesson,
            'course'     => $course,
            'courseSet'  => $courseSet,
            'chapterId' => $chapterId,
            'stage'      => $stage,
            'teachingAims' => $this->getTeachingAimService()->processAims($aims),
        ));
    }

    private function createTask(Request $request, $task, $lesson)
    {
        $chapterId              = $task['chapterId'];
        $stage                   = $task['stage'];
        $course                  = $this->getCourseService()->tryManageCourse($lesson['courseId']);
        $task['_base_url']       = $request->getSchemeAndHttpHost();
        $task['fromUserId']      = $this->getUser()->getId();
        $task['fromCourseSetId'] = $course['courseSetId'];
        $task['mode']            = 'lesson';
        $task['status']           = 'published';

        $task = $this->getTaskService()->createTask($this->parseTimeFields($task));
        $this->getCourseLessonService()->createLessonTask(array(
            'taskId'    => $task['id'],
            'courseId'  => $course['id'],
            'lessonId'  => $lesson['id'],
            'chapterId' => $chapterId,
            'stage'      => $stage,
            'aimIds'    => $request->request->get('aimIds', array()),
        ));

        if ($course['isDefault'] && isset($task['mode']) && $task['mode'] != 'lesson') {
            return $this->createJsonResponse(array(
                'append' => false,
                'html'   => ''
            ));
        }

        $task = $this->prepareRenderTask($course, $task, $lesson, $chapterId);

        $html = $this->renderView($this->createCourseStrategy($course)->getTaskItemTemplate($course), array(
            'course' => $course,
            'task'   => $task
        ));

        return $this->createJsonResponse(array(
            'append' => true,
            'html'   => $html
        ));
    }

    private function prepareRenderTask($course, $task, $lesson, $chapterId)
    {
        if (!$course['isDefault']) {
            return $task;
        }
        $courseLessonChapter = $this->getCourseLessonService()->getChapter($chapterId);
        if (empty($courseLessonChapter['categoryId'])) {
            $parentId = 0;
        } else {
            $parentId = $courseLessonChapter['categoryId'];
        }
        $chapter          = $this->getChapterDao()->get($task['categoryId']);

        $lessontask = $this->getCourseLessonService()->getLessonTaskByTaskId($task['id']);
        $seq = $this->getCourseService()->getNextCourseLessonItemSeq($course['id'], $lesson['id'], $parentId, $lessontask['stage']);
        $fields = array(
            'lessonId' => $lesson['id'],
            'seq' => $seq,
            'parentId' => $parentId,
            'stage' => $lessontask['stage'],
        );
        $chapter = $this->getCourseService()->updateChapter($course['id'], $chapter['id'], $fields);

        $tasks            = $this->getTaskService()->findTasksFetchActivityByChapterId($chapter['id']);
        $chapter['tasks'] = $tasks;
        $chapter['mode']  = $task['mode'];

        return $chapter;
    }

    public function updateAction(Request $request, $courseId, $id)
    {
        $course = $this->tryManageCourse($courseId);
        $task = $this->getTaskService()->getTask($id);
        $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($task['id']);
        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonTask['lessonId']);
        if ($lesson['status'] == 'teaching') {
            return $this->createMessageResponse('error', '该课次正在上课，禁止编辑活动！');
        }
        $taskMode = $request->query->get('type');
        if ($task['courseId'] != $courseId) {
            throw new InvalidArgumentException('任务不在计划中');
        }

        if ($request->getMethod() == 'POST') {
            $fields = $request->request->all();

            $task = $this->getTaskService()->updateTask($id, $this->parseTimeFields($fields));
            $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($task['id']);
            if (empty($fields['aimIds'])) {
                $fields['aimIds'] = array();
            }
            $this->getTeachingAimActivityService()->connectAims($task['activityId'], $lessonTask['lessonId'], $fields['aimIds']);

            return $this->createJsonResponse(array('append' => false, 'html' => ''));
        }

        $activity = $this->getActivityService()->getActivity($task['activityId']);
        $courseSet = $this->getCourseSetService()->getCourseSet($course['courseSetId']);

        $aims = $this->getTeachingAimService()->findByLessonId($lesson['id']);
        $relations = $this->getTeachingAimActivityService()->findByActivityId($task['activityId']);
        $aimsIds = ArrayToolkit::column($relations, 'aimId');

        return $this->render(
            'prepare-course-manage/lesson-manage/task-create-modal.html.twig',
            array(
                'mode' => 'edit',
                'currentType' => $activity['mediaType'],
                'course' => $course,
                'courseSet' => $courseSet,
                'task' => $task,
                'taskMode' => $taskMode,
                'stage' => $lessonTask['stage'],
                'connAimIds' => $aimsIds,
                'teachingAims' => $this->getTeachingAimService()->processAims($aims),
            )
        );
    }

    public function taskFieldsAction(Request $request, $courseId, $mode)
    {
        $course = $this->tryManageCourse($courseId);
        $stage = $request->query->get('stage');

        if ($mode === 'create') {
            $type = $request->query->get('type');

            return $this->forward(
                'CustomBundle:Activity/Activity:create',
                array(
                    'courseId' => $courseId,
                    'type' => $type,
                    'stage' => $stage,
                )
            );
        } else {
            $id = $request->query->get('id');
            $task = $this->getTaskService()->getTask($id);

            return $this->forward(
                'CustomBundle:Activity/Activity:update',
                array(
                    'id' => $task['activityId'],
                    'courseId' => $courseId,
                    'stage' => $stage,
                )
            );
        }
    }
    
    public function deleteAction(Request $request, $courseId, $taskId)
    {
        $task = $this->getTaskService()->getTask($taskId);
        if ($task['courseId'] != $courseId) {
            throw new InvalidArgumentException('任务不在课程中');
        }

        $this->getTaskService()->deleteTask($taskId);

        return $this->createJsonResponse(array('success' => true));
    }

    protected function getTeachingAimService()
    {
        return $this->createService('CustomBundle:Lesson:TeachingAimService');
    }

    protected function getTeachingAimActivityService()
    {
        return $this->createService('CustomBundle:Lesson:TeachingAimActivityService');
    }

    protected function getCourseLessonService()
    {
        return $this->createService('CustomBundle:Course:CourseLessonService');
    }

    protected function getCourseService()
    {
        return $this->createService('CustomBundle:Course:CourseService');
    }
}
