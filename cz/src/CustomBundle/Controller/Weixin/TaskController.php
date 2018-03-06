<?php

namespace CustomBundle\Controller\Weixin;

use CustomBundle\Biz\Course\Service;
use AppBundle\Common\ArrayToolkit;
use Biz\User\Service\TokenService;
use Symfony\Component\HttpFoundation\Request;
use CustomBundle\Controller\Weixin\WeixinBaseController;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class TaskController extends WeixinBaseController
{
    public function startInstantCourseTaskAction($courseId, $lessonId, $taskId)
    {
        $this->getCourseLessonService()->tryManageCourseLesson($lessonId);
        $status = $this->getTaskService()->startInstantCourseTask($taskId);

        return $this->createJsonResponse(array('status' => $status['status']));
    }

    public function endInstantCourseTaskAction($courseId, $lessonId, $taskId)
    {
        $this->getCourseLessonService()->tryManageCourseLesson($lessonId);
        $status = $this->getStatusService()->endTask($taskId);

        return $this->createJsonResponse(array('status' => $status['status']));
    }

    public function startLessonAction(Request $request)
    {
        $request = $request->query->all();
        $lessonId = $request['lessonId'];
        $user = $this->getCurrentUser();
        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonId);
        if (empty($lesson)) {
            return $this->createJsonResponse(array(
                'message' => '系统错误',
            ));
        }
        $member = $this->getCourseMemberService()->getCourseMember($lesson['courseId'], $user['id']);
        if (!$user->isTeacher() || $member['role'] != 'teacher') {
            return $this->createJsonResponse(array(
                'message' => '权限不够',
            ));
        }

        $task = $this->getTaskService()->getFirstInClassTaskByLessonId($lessonId);
        if (empty($task)) {
            return $this->createJsonResponse(array(
                'message' => '暂无课堂内容',
            ));
        }

        $lessonStatus = $lesson['status'];
        if ($lesson['status'] == 'created') {
            $this->getCourseLessonService()->startCourseLesson($lessonId);
            $lessonStatus = 'teaching';
        }

        $result = array(
            'lessonStatus' => $lessonStatus,
            'next' => array(
                'taskId' => $task['id'],
                'activityId' => $task['activityId'],
                'activityType' => $task['type']
            )
        );

        return $this->createJsonResponse($result);
    }

    public function lessonCancelAction(Request $request)
    {
        $request = $request->query->all();
        $user = $this->getCurrentUser();
        $lessonId = $request['lessonId'];
        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonId);
        if (empty($lesson)) {
            return $this->createJsonResponse(array(
                'message' => '系统错误',
            ), 500);
        }
        $member = $this->getCourseMemberService()->getCourseMember($lesson['courseId'], $user['id']);
        if (!$user->isTeacher() || $member['role'] != 'teacher') {
            return $this->createJsonResponse(array(
                'message' => '权限不够',
            ), 403);
        }
        $this->getCourseLessonService()->tryManageCourseLesson($lessonId);
        $this->getCourseLessonService()->cancelCourseLesson($lessonId);

        return $this->createJsonResponse(true);
    }

    public function lessonTaskAction(Request $request, $courseId, $lessonId)
    {
        $request = $request->query->all();
        $taskId = $request['taskId'];
        $task = $this->getTaskService()->getTask($taskId);
        $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($taskId);
        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonId);
        $user = $this->getCurrentUser();
        $member = $this->getCourseMemberService()->getCourseMember($courseId, $user['id']);

        if ($lesson['status'] == 'created' && $lessonTask['stage'] != 'before' && $member['role'] != 'teacher') {
            return $this->createJsonResponse(array(
                'message' => '课程未开始',
            ));
        }
        $this->startTask($taskId, $lesson, $lessonTask);

        $activityStatus = $this->taskStatus($lesson['id'], $courseId);
        if ($lesson['status'] != 'teaching') {
            $activityStatus = 'false';
        }

        $activity = $this->getActivityService()->getActivity($task['activityId']);
        $activityNumber = $this->getCourseService()->countChapterByChapterId($task['categoryId']);
        $member = $this->getCourseMemberService()->getCourseMember($courseId, $user['id']);

        if ($member['role'] == 'teacher') {
            $this->getLessonRecordService()->changeLessonRecordByLessonId($lessonTask['lessonId'], $taskId);
        }

        $result = array(
            'id' => $activity['id'],
            'role' => $member['role'],
            'lessonNumber' => $lesson['number'],
            'lessonTitle' => $lesson['title'],
            'status' => $lesson['status'],
            'activityStatus' => $activityStatus,
            'activityNumber' => $activityNumber,
            'activityTitle' => $activity['title'],
            'activityContent' => $activity['content'],
            'activityType' => $activity['mediaType'],
            'duration' => $activity['duration'],
            'about' => $activity['about'],
            'stage' => $lessonTask['stage'],
            'next' => $this->concisionTask($this->getTaskService()->getNextTask($taskId)),
            'up' => $this->concisionTask($this->getTaskService()->getPreviousTask($taskId)),
        );

        if ($result['activityType'] == 'displayWall' && $lesson['status'] == 'teaching') {
            $status = $this->getStatusService()->getStatusByTaskId($taskId);
            $result['status'] = $status['status'];
        }

        return $this->createJsonResponse($result);
    }

    protected function concisionTask($task)
    {
        if (empty($task)) {
            return array();
        }
        $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($task['id']);
        $result = array(
            'activityId' => $task['id'],
            'activityType' => $task['type'],
            'taskId' => $task['id'],
            'stage' => $lessonTask['stage'],
        );

        if ($lessonTask['stage'] == 'in') {
            $item = $this->getTaskResultService()->isStartClassByTaskId($result['taskId']);
            if (!$item) {
                $result['isLock'] = false;
            }
        }

        return $result;
    }

    public function studentInTaskAction(Request $request, $courseId)
    {
        $record = $this->getLessonRecordService()->getByCourseId($courseId);
        $task = $this->taskAnalysis($record['taskId']);
        $task['lessonId'] = $record['lessonId'];
        $task['courseId'] = $record['courseId'];

        if (empty($record)) {
            $lesson = $this->getCourseLessonService()->getTeachingCourseLessonByCourseId($courseId);
            $task['lessonId'] = $lesson['id'];
        }

        return $this->createJsonResponse($task);
    }

    public function studentTaskAction(Request $request, $courseId, $taskId)
    {
        return $this->createJsonResponse($this->taskAnalysis($taskId));
    }

    protected function taskAnalysis($taskId)
    {

        $task = $this->getTaskService()->getTask($taskId);
        $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($taskId);
        $activity = $this->getActivityService()->getActivity($task['activityId']);
        $status = $this->getStatusService()->getStatusByTaskId($taskId);

        $result = array(
            'taskId' => $taskId,
            'activityTitle' => $activity['title'],
            'activityContent' => $activity['content'],
            'activityType' => $activity['mediaType'],
            'duration' => $activity['duration'],
            'about' => $activity['about'],
            'status' => empty($status) ? false : $status['status'],
            'activityId' => empty($task['activityId']) ? 0 : $task['activityId'],
        );

        if ($taskId == 0) {
            $result['activityType'] = 'collectBeforeTasks';
        }

        return $result;
    }

    public function rollcallStudentAction(Request $request)
    {
        $result = $request->query->all();
        $taskId = $result['taskId'];
        $results = $this->getRollcallResultService()->findResultsByTaskId($taskId);
        $results = ArrayToolkit::index($results, 'userId');

        $userIds = ArrayToolkit::column($results, 'userId');
        $users = $this->getUserService()->findUsersByIds($userIds);

        $students = array();
        foreach ($userIds as $userId) {
            $avatar = $this->get('web.twig.app_extension')->userAvatar($users[$userId], 'small');
            $userFile = $this->getUserService()->getUserProfile($users[$userId]['id']);
            $result = $results[$users[$userId]['id']];

            $students[] = array(
                'id' => $users[$userId]['id'],
                'number' => $users[$userId]['number'],
                'truename' => $users[$userId]['truename'],
                'nickname' => $users[$userId]['truename'],
                'resultId' => $result['id'],
                'score' => $result['score'],
                'avatar' => empty($avatar) ? null : $this->getWebExtension()->getFpath($avatar, 'avatar.png')
            );
        }

        $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($taskId);
        $randomStudentIds = $this->getCourseMemberService()->findRandomStudentIdsByLessonId($lessonTask['lessonId'], $userIds, 1);

        return $this->createJsonResponse(array(
            'students' => $students,
            'canRand' => !empty($randomStudentIds),
        ));
    }

    protected function taskStatus($lessonId, $courseId)
    {
        $lessonItems = $this->getCourseLessonService()->findCourseLessonItems($lessonId);
        $course = $this->getCourseService()->getCourse($courseId);
        $courseSet = $this->getCourseSetService()->getCourseSet($course['courseSetId']);
        $status = $this->currentTask($lessonItems['in'], $courseSet['teacherIds']);

        return empty($status) ? 'false' : 'start';
    }

    protected function startTask($taskId, $lesson, $lessonTask)
    {
        $taskResult = $this->getTaskResultService()->getUserTaskResultByTaskId($taskId);
        if (empty($taskResult) && $lesson['status'] != 'created' && $lessonTask['stage'] == 'in') {
            return $this->getTaskService()->startTask($taskId);
        }

        if (empty($taskResult) && $lessonTask['stage'] == 'before') {
            return $this->getTaskService()->startTask($taskId);
        }

        if (empty($taskResult) && $lesson['status'] == 'teached' && $lessonTask['stage'] == 'after') {
            return $this->getTaskService()->startTask($taskId);
        }

        return false;
    }

    protected function tryLearnTask($courseId, $taskId, $preview = false)
    {
        if ($preview) {
            if ($this->getCourseService()->hasCourseManagerRole($courseId)) {
                $task = $this->getTaskService()->getTask($taskId);
            } else {
                throw $this->createNotFoundException('you can not preview this task ');
            }
        } else {
            $task = $this->getTaskService()->tryTakeTask($taskId);
        }
        if (empty($task)) {
            return array();
        }

        if ($task['courseId'] != $courseId) {
            return array();
        }

        return $task;
    }

    protected function getTaskResultService()
    {
        return $this->createService('CustomBundle:Task:TaskResultService');
    }

    protected function getCourseMemberService()
    {
        return $this->createService('CustomBundle:Course:MemberService');
    }

    protected function getActivityService()
    {
        return $this->createService('Activity:ActivityService');
    }

    protected function getRollcallResultService()
    {
        return $this->createService('CustomBundle:Activity:RollcallResultService');
    }

    protected function getStatusService()
    {
        return $this->createService('CustomBundle:Task:TaskStatusService');
    }

    protected function getLessonRecordService()
    {
        return $this->createService('CustomBundle:Lesson:RecordService');
    }
}
