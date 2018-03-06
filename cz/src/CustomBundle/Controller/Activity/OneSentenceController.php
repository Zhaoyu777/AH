<?php

namespace CustomBundle\Controller\Activity;

use AppBundle\Controller\BaseController;
use AppBundle\Common\ArrayToolkit;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Controller\Activity\ActivityActionInterface;

class OneSentenceController extends BaseController
{
    public function previewAction(Request $request, $task)
    {
        $activity = $this->getActivityService()->getActivity($task['activityId']);
        $course = $this->getCourseService()->getCourse($task['courseId']);

        return $this->render('activity/one-sentence/preview.html.twig', array(
            'course' => $course,
            'activity' => $activity,
            'task' => $task,
        ));
    }

    public function showAction(Request $request, $activity, $task, $mode)
    {
        $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($task['id']);
        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonTask['lessonId']);

        $results = $this->getResultService()->findResultsByTaskId($task['id']);

        $userIds = ArrayToolkit::column($results, 'userId');
        $users = $this->getUserService()->findUsersByIds($userIds);

        $results = ArrayToolkit::group($results, 'groupId');

        $groups = $this->getCourseGroupService()->findCourseGroupsByCourseIdWithMembers($task['courseId']);
        $groupIds = ArrayToolkit::column($groups, 'id');
        $groupMember = $this->getGroupMemberService()->findGroupMembersByGroupIdsWithUserId($groupIds);
        $course = $this->getCourseService()->getCourse($lesson['courseId']);

        return $this->render('activity/one-sentence/show.html.twig', array(
            'task' => $task,
            'lesson' => $lesson,
            'results' => $results,
            'activity' => $activity,
            'users' => $users,
            'groups' => $groups,
            'isGrouped' => count($groupIds) > 1,
            'resultCount' => count($results),
            'groupMember' => ArrayToolkit::group($groupMember, 'groupId'),
            'course' => $course,
            'mode' => $mode,
        ));
    }

    public function editAction(Request $request, $id, $courseId)
    {
        $activity = $this->getActivityService()->getActivity($id);
        $config = $this->getActivityService()->getActivityConfig('oneSentence');
        $oneSentence = $config->get($activity['mediaId']);

        return $this->render('activity/one-sentence/modal.html.twig', array(
            'activity' => $activity,
            'oneSentence' => $oneSentence,
        ));
    }

    public function createAction(Request $request, $courseId)
    {
        return $this->render('activity/one-sentence/modal.html.twig', array(
            'courseId' => $courseId,
        ));
    }

    public function startAction($taskId, $activityId)
    {
        $this->getStatusService()->startTask($taskId, $activityId);

        return $this->createJsonResponse(true);
    }

    public function endAction($taskId)
    {
        $this->getStatusService()->endTask($taskId);

        return $this->createJsonResponse(true);
    }

    public function finishConditionAction(Request $request, $activity)
    {
        return $this->render('activity/discuss/finish-condition.html.twig', array());
    }

    public function loadResultsAction($taskId, $activityId)
    {
        $task = $this->getTaskService()->getTask($taskId);
        $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($task['id']);
        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonTask['lessonId']);

        $results = $this->getResultService()->findResultsByTaskId($taskId);
        $userIds = ArrayToolkit::column($results, 'userId');
        $users = $this->getUserService()->findUsersByIds($userIds);
        $results = ArrayToolkit::group($results, 'groupId');

        if ($lesson['status'] == 'teaching') {
            $groups = $this->getCourseGroupService()->findCourseGroupsByCourseIdWithMembers($task['courseId']);
            $groupIds = ArrayToolkit::column($groups, 'id');
        } else {
            $groupIds = array_keys($results);
        }

        return $this->render('activity/one-sentence/result-tr.html.twig', array(
            'results' => $results,
            'users' => $users,
            'groupIds' => $groupIds,
            'isGrouped' => count($groupIds) > 1,
            'resultCount' => count($results),
        ));
    }

    protected function getStatusService()
    {
        return $this->createService('CustomBundle:Task:TaskStatusService');
    }

    protected function getResultService()
    {
        return $this->createService('CustomBundle:Activity:OneSentenceResultService');
    }

    protected function getCourseGroupService()
    {
        return $this->createService('CustomBundle:Course:CourseGroupService');
    }

    protected function getCourseMemberService()
    {
        return $this->createService('Course:MemberService');
    }

    protected function getTaskService()
    {
        return $this->createService('CustomBundle:Task:TaskService');
    }

    protected function getGroupMemberService()
    {
        return $this->createService('CustomBundle:Course:GroupMemberService');
    }

    protected function getCourseLessonService()
    {
        return $this->createService('CustomBundle:Course:CourseLessonService');
    }

    protected function getActivityService()
    {
        return $this->createService('Activity:ActivityService');
    }

    protected function getUserService()
    {
        return $this->createService('User:UserService');
    }

    protected function getTaskGroupService()
    {
        return $this->createService('CustomBundle:TaskGroup:TaskGroupService');
    }

    protected function getCourseService()
    {
        return $this->createService('Course:CourseService');
    }
}
