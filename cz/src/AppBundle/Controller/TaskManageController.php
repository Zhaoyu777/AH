<?php

namespace AppBundle\Controller;

use Biz\Activity\Service\ActivityService;
use Biz\Course\Service\CourseService;
use Biz\Course\Service\CourseSetService;
use Biz\File\Service\UploadFileService;
use Biz\Task\Service\TaskService;
use Biz\Task\Strategy\CourseStrategy;
use AppBundle\Util\UploaderToken;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Common\Exception\InvalidArgumentException;

class TaskManageController extends BaseController
{
    public function preCreateCheckAction(Request $request, $courseId)
    {
        $task = $request->request->all();
        $task['fromCourseId'] = $courseId;
        try {
            $this->getTaskService()->preCreateTaskCheck($this->parseTimeFields($task));

            return $this->createJsonResponse(array('success' => 1));
        } catch (\Exception $e) {
            return $this->createJsonResponse(array('success' => 0, 'error' => $e->getMessage()));
        }
    }

    public function preUpdateCheckAction(Request $request, $courseId, $taskId)
    {
        $task = $request->request->all();
        $task['fromCourseId'] = $courseId;
        try {
            $this->getTaskService()->preUpdateTaskCheck($taskId, $this->parseTimeFields($task));

            return $this->createJsonResponse(array('success' => 1));
        } catch (\Exception $e) {
            return $this->createJsonResponse(array('success' => 0, 'error' => $e->getMessage()));
        }
    }

    public function createAction(Request $request, $courseId)
    {
        $course = $this->tryManageCourse($courseId);
        $categoryId = $request->query->get('categoryId');
        $chapterId = $request->query->get('chapterId');
        $taskMode = $request->query->get('type');
        if ($request->isMethod('POST')) {
            $task = $request->request->all();

            return $this->createTask($request, $task, $course);
        }
        $courseSet = $this->getCourseSetService()->getCourseSet($course['courseSetId']);

        return $this->render(
            'task-manage/modal.html.twig',
            array(
                'mode' => 'create',
                'course' => $course,
                'courseSet' => $courseSet,
                'categoryId' => $categoryId,
                'chapterId' => $chapterId,
                'taskMode' => $taskMode,
            )
        );
    }

    protected function prepareRenderTaskForDefaultCourseType($courseType, $task)
    {
        if ($courseType != CourseService::DEFAULT_COURSE_TYPE) {
            return $task;
        }
        $chapter = $this->getChapterDao()->get($task['categoryId']);
        $tasks = $this->getTaskService()->findTasksFetchActivityByChapterId($chapter['id']);
        $chapter['tasks'] = $tasks;
        $chapter['mode'] = $task['mode'];

        return $chapter;
    }

    public function batchCreateTasksAction(Request $request, $courseId)
    {
        $this->getCourseService()->tryManageCourse($courseId);
        $mode = $request->query->get('mode');
        if ($request->isMethod('POST')) {
            $fileId = $request->request->get('fileId');
            $file = $this->getUploadFileService()->getFile($fileId);

            if (!in_array($file['type'], array('document', 'video', 'audio', 'ppt', 'flash'))) {
                return $this->createJsonResponse(array('error' => '不支持的文件类型'));
            }

            $course = $this->getCourseService()->getCourse($courseId);
            $task = $this->createTaskByFileAndCourse($file, $course);
            $task['mode'] = $mode;

            return $this->createTask($request, $task, $course);
        }

        $token = $request->query->get('token');
        $parser = new UploaderToken();
        $params = $parser->parse($token);

        if (!$params) {
            return $this->createJsonResponse(array('error' => 'bad token'));
        }

        return $this->render(
            'course-manage/batch-create/batch-create-modal.html.twig',
            array(
                'token' => $token,
                'targetType' => $params['targetType'],
                'courseId' => $courseId,
                'mode' => $mode,
            )
        );
    }

    private function createTaskByFileAndCourse($file, $course)
    {
        $task = array(
            'mediaType' => $file['type'],
            'fromCourseId' => $course['id'],
            'courseSetType' => 'normal',
            'media' => json_encode(array('source' => 'self', 'id' => $file['id'], 'name' => $file['filename'])),
            'mediaId' => $file['id'],
            'type' => $file['type'],
            'length' => $file['length'],
            'title' => str_replace(strrchr($file['filename'], '.'), '', $file['filename']),
            'ext' => array('mediaSource' => 'self', 'mediaId' => $file['id']),
            'categoryId' => 0,
        );
        if ($file['type'] == 'document') {
            $task['type'] = 'doc';
            $task['mediaType'] = 'doc';
        }

        return $task;
    }

    /**
     * @param Request $request
     * @param         $task
     * @param         $course
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    private function createTask(Request $request, $task, $course)
    {
        $task['_base_url'] = $request->getSchemeAndHttpHost();
        $task['fromUserId'] = $this->getUser()->getId();
        $task['fromCourseSetId'] = $course['courseSetId'];

        $task = $this->getTaskService()->createTask($this->parseTimeFields($task));

        if ($course['courseType'] == CourseService::DEFAULT_COURSE_TYPE && isset($task['mode']) && $task['mode'] != 'lesson') {
            return $this->createJsonResponse(
                array(
                    'append' => false,
                    'html' => '',
                )
            );
        }

        $task = $this->prepareRenderTaskForDefaultCourseType($course['courseType'], $task);

        $html = $this->renderView(
            $this->createCourseStrategy($course)->getTaskItemTemplate(),
            array(
                'course' => $course,
                'task' => $task,
            )
        );

        return $this->createJsonResponse(
            array(
                'append' => true,
                'html' => $html,
            )
        );
    }

    public function updateAction(Request $request, $courseId, $id)
    {
        $course = $this->tryManageCourse($courseId);
        $task = $this->getTaskService()->getTask($id);
        $taskMode = $request->query->get('type');
        if ($task['courseId'] != $courseId) {
            throw new InvalidArgumentException('任务不在计划中');
        }

        if ($request->getMethod() == 'POST') {
            $task = $request->request->all();

            if (!isset($task['isOptional'])) {
                $task['isOptional'] = 0;
            }

            $this->getTaskService()->updateTask($id, $this->parseTimeFields($task));

            return $this->createJsonResponse(array('append' => false, 'html' => ''));
        }

        $activity = $this->getActivityService()->getActivity($task['activityId']);
        $courseSet = $this->getCourseSetService()->getCourseSet($course['courseSetId']);

        return $this->render(
            'task-manage/modal.html.twig',
            array(
                'mode' => 'edit',
                'currentType' => $activity['mediaType'],
                'course' => $course,
                'courseSet' => $courseSet,
                'task' => $task,
                'taskMode' => $taskMode,
            )
        );
    }

    public function publishAction(Request $request, $courseId, $id)
    {
        $this->tryManageCourse($courseId);
        $this->getTaskService()->publishTask($id);

        return $this->createJsonResponse(array('success' => true));
    }

    public function unPublishAction(Request $request, $courseId, $id)
    {
        $this->tryManageCourse($courseId);
        $this->getTaskService()->unpublishTask($id);

        return $this->createJsonResponse(array('success' => true));
    }

    public function taskFieldsAction(Request $request, $courseId, $mode)
    {
        $course = $this->tryManageCourse($courseId);

        if ($mode === 'create') {
            $type = $request->query->get('type');

            return $this->forward(
                'AppBundle:Activity/Activity:create',
                array(
                    'courseId' => $courseId,
                    'type' => $type,
                )
            );
        } else {
            $id = $request->query->get('id');
            $task = $this->getTaskService()->getTask($id);

            return $this->forward(
                'AppBundle:Activity/Activity:update',
                array(
                    'id' => $task['activityId'],
                    'courseId' => $courseId,
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
        if (isset($task['mode']) && $task['mode'] == 'lesson') {
            $this->getCourseService()->deleteChapter($task['courseId'], $task['categoryId']);
        }

        return $this->createJsonResponse(array('success' => true));
    }

    protected function tryManageCourse($courseId)
    {
        return $this->getCourseService()->tryManageCourse($courseId);
    }

    /**
     * @return CourseService
     */
    protected function getCourseService()
    {
        return $this->createService('Course:CourseService');
    }

    /**
     * @return TaskService
     */
    protected function getTaskService()
    {
        return $this->createService('Task:TaskService');
    }

    /**
     * @return ActivityService
     */
    protected function getActivityService()
    {
        return $this->createService('Activity:ActivityService');
    }

    protected function getActivityConfig()
    {
        return $this->get('extension.manager')->getActivities();
    }

    /**
     * @param $course
     *
     * @return CourseStrategy
     */
    protected function createCourseStrategy($course)
    {
        return $this->getBiz()->offsetGet('course.strategy_context')->createStrategy($course['courseType']);
    }

    protected function parseTimeFields($fields)
    {
        if (!empty($fields['startTime'])) {
            $fields['startTime'] = strtotime($fields['startTime']);
        }
        if (!empty($fields['endTime'])) {
            $fields['endTime'] = strtotime($fields['endTime']);
        }

        return $fields;
    }

    /**
     * @return CourseSetService
     */
    protected function getCourseSetService()
    {
        return $this->createService('Course:CourseSetService');
    }

    /**
     * @return UploadFileService
     */
    protected function getUploadFileService()
    {
        return $this->createService('File:UploadFileService');
    }

    protected function getChapterDao()
    {
        return $this->getBiz()->dao('Course:CourseChapterDao');
    }
}
