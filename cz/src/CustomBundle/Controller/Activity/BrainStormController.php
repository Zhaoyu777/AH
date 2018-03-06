<?php

namespace CustomBundle\Controller\Activity;

use AppBundle\Controller\BaseController;
use AppBundle\Common\ArrayToolkit;
use Symfony\Component\HttpFoundation\Request;

class BrainStormController extends BaseController
{
    public function previewAction(Request $request, $task)
    {
        $activity = $this->getActivityService()->getActivity($task['activityId']);
        $config = $this->getActivityService()->getActivityConfig('brainStorm');
        $brainStorm = $config->get($activity['mediaId']);
        $course = $this->getCourseService()->getCourse($task['courseId']);

        return $this->render('activity/brain-storm/preview.html.twig', array(
            'course' => $course,
            'task' => $task,
            'activity' => $activity,
            'brainStorm' => $brainStorm,
        ));
    }

    public function showAction(Request $request, $activity, $task, $mode)
    {
        $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($task['id']);
        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonTask['lessonId']);

        $config = $this->getActivityService()->getActivityConfig('brainStorm');
        $brainStorm = $config->get($activity['mediaId']);
        $groups = $this->buildResults($task, $brainStorm);
        $course = $this->getCourseService()->getCourse($lesson['courseId']);

        return $this->render('activity/brain-storm/show.html.twig', array(
            'task' => $task,
            'lesson' => $lesson,
            'groups' => $groups,
            'activity' => $activity,
            'brainStorm' => $brainStorm,
            'course' => $course,
            'mode' => $mode,
        ));
    }

    private function buildResults($task, $brainStorm)
    {
        $groups = $this->getTaskGroupService()->findTaskGroupsByTaskId($task['id']);
        $results = $this->getResultService()->findResultsByTaskId($task['id']);
        $userIds = ArrayToolkit::column($results, 'userId');
        $users = $this->getUserService()->findUsersByIds($userIds);

        foreach ($results as $key => $result) {
            $results[$key]['truename'] = $users[$result['userId']]['truename'];
            $results[$key]['number'] = $users[$result['userId']]['number'];
        }

        $results = ArrayToolkit::group($results, 'groupId');
        $response = array();

        if ($brainStorm['submitWay'] == 'person') {
            $memberCounts = $this->getTaskGroupService()->countTaskGroupMembersByTaskIdGroupByGroupId($task['id']);
            $memberCounts = ArrayToolkit::index($memberCounts, 'groupId');
        }

        foreach ($groups as $key => $group) {
            $tmpGroup['id'] = $group['id'];
            $tmpGroup['title'] = $group['title'];
            $tmpGroup['results'] = empty($results[$group['id']]) ? array() : $results[$group['id']];
            if ($brainStorm['submitWay'] == 'person') {
                $tmpGroup['memberCount'] = empty($memberCounts[$group['id']]) ? 0 : $memberCounts[$group['id']]['count'];
                $tmpGroup['replyCount'] = count($tmpGroup['results']);
            }
            if (empty($defaultGroup)) {
                $defaultGroup = $tmpGroup;
            } else {
                $response[$key] = $tmpGroup;
            }
        }

        if (!empty($defaultGroup) && $brainStorm['groupWay'] != 'random') {
            $response[] = $defaultGroup;
        }

        if (!empty($defaultGroup) && $brainStorm['groupWay'] == 'random') {
            array_unshift($response, $defaultGroup);
        }

        return $response;
    }

    public function createAction(Request $request, $courseId)
    {
        return $this->render('activity/brain-storm/modal.html.twig', array(
            'courseId' => $courseId,
        ));
    }

    public function editAction(Request $request, $id, $courseId)
    {
        $activity = $this->getActivityService()->getActivity($id);
        $config = $this->getActivityService()->getActivityConfig('brainStorm');
        $brainStorm = $config->get($activity['mediaId']);

        return $this->render('activity/brain-storm/modal.html.twig', array(
            'activity' => $activity,
            'brainStorm' => $brainStorm,
        ));
    }

    public function remarkAction(Request $request, $resultId)
    {
        if ($request->getMethod() == 'POST') {
            $fields = $request->request->all();
            $result = $this->getResultService()->remark($resultId, $fields);

            return $this->createJsonResponse(true);
        }
        $result = $this->getResultService()->getResult($resultId);
        $user = $this->getUserService()->getUser($result['userId']);
        $group = $this->getTaskGroupService()->getGroup($result['groupId']);

        return $this->render('activity/brain-storm/remark.html.twig', array(
            'result' => $result,
            'user' => $user,
            'group' => $group,
        ));
    }

    public function groupRemarkAction(Request $request, $taskId, $groupId)
    {
        if ($request->getMethod() == 'POST') {
            $fields = $request->request->all();

            $result = $this->getResultService()->groupRemark($fields);

            return $this->createJsonResponse(true);
        }
        $results = $this->getResultService()->findResultsByTaskIdAndGroupId($taskId, $groupId);

        $userIds = ArrayToolkit::column($results, 'userId');
        $users = $this->getUserService()->findUsersByIds($userIds);
        $group = $this->getTaskGroupService()->getGroup($groupId);

        return $this->render('activity/brain-storm/group-remark.html.twig', array(
            'results' => $results,
            'users' => $users,
            'taskId' => $taskId,
            'group' => $group
        ));
    }

    protected function getTaskStatusService()
    {
        return $this->createService('CustomBundle:Task:TaskStatusService');
    }

    protected function getResultService()
    {
        return $this->createService('CustomBundle:Activity:BrainStormResultService');
    }

    protected function getTaskGroupService()
    {
        return $this->createService('CustomBundle:TaskGroup:TaskGroupService');
    }

    protected function getCourseGroupService()
    {
        return $this->createService('CustomBundle:Course:CourseGroupService');
    }

    protected function getTaskService()
    {
        return $this->createService('CustomBundle:Task:TaskService');
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

    protected function getCourseService()
    {
        return $this->createService('Course:CourseService');
    }
}
