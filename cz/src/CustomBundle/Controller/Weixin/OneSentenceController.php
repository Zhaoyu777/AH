<?php

namespace CustomBundle\Controller\Weixin;

use AppBundle\Common\ArrayToolkit;
use AppBundle\Controller\BaseController;
use Symfony\Component\HttpFoundation\Request;

class OneSentenceController extends BaseController
{
    public function answerAction(Request $request, $taskId)
    {
        $task = $this->getTaskService()->getTask($taskId);
        $user = $this->getCurrentUser();

        $result = $this->getResultService()->getResultByTaskIdAndUserId($task['id'], $user['id']);
        if (!empty($result)) {
            return $this->createJsonResponse(array('message' => '你已提交结果，请勿重复提交。'));
        }

        $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($task['id']);
        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonTask['lessonId']);

        if ($lesson['status'] != 'teaching') {
            return $this->createJsonResponse(array('message' => '课次未开始或已下课，不能提交结果。'));
        }

        $status = $this->getStatusService()->getStatusByActivityId($task['activityId']);
        if (!(!empty($status) && $status['status'] == 'start')) {
            return $this->createJsonResponse(array('message' => '活动未开始。'));
        }

        $courseMember = $this->getCourseMemberService()->getCourseMember($task['courseId'], $user['id']);
        if (empty($courseMember)) {
            return $this->createJsonResponse(array('message' => '你不是该班级成员。'));
        }

        if ($courseMember['role'] == 'teacher') {
            return $this->createJsonResponse(array('message' => '你是老师，不能回答。'));
        }
        $groupMember = $this->getGroupMemberService()->getGroupMemberByCourseMemberId($courseMember['id']);

        $content = $request->request->get('content');
        if (empty($content)) {
            return $this->createJsonResponse(array('message' => '回答不能为空。'));
        }

        $fileds = array(
            'activityId' => $task['activityId'],
            'courseId' => $task['courseId'],
            'courseTaskId' => $task['id'],
            'courseId' => $task['courseId'],
            'userId' => $user['id'],
            'groupId' => $groupMember['groupId'],
            'content' => $content,
        );

        $result = $this->getResultService()->createResult($fileds);
        $activity = $this->getActivityService()->getActivity($task['activityId']);

        return $this->createJsonResponse(array(
            'score' => $activity['score'],
            'result' => $result
        ));
    }

    public function startAction($taskId, $activityId)
    {
        $user = $this->getCurrentUser();
        $task = $this->getTaskService()->getTask($taskId);
        $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($taskId);
        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonTask['lessonId']);

        if ($lesson['status'] != 'teaching') {
            return $this->createJsonResponse(array('message' => '课次未开始或已下课，不能开始活动。'));
        }

        $courseMember = $this->getCourseMemberService()->getCourseMember($task['courseId'], $user['id']);

        if ($courseMember['role'] != 'teacher') {
            return $this->createJsonResponse(array('message' => '你不是老师，不能开始活动。'));
        }

        $status = $this->getStatusService()->startTask($taskId, $activityId);

        return $this->createJsonResponse(array('status' => $status['status']));
    }

    public function endAction($taskId, $activityId)
    {
        $user = $this->getCurrentUser();
        $task = $this->getTaskService()->getTask($taskId);
        $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($taskId);
        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonTask['lessonId']);

        if ($lesson['status'] != 'teaching') {
            return $this->createJsonResponse(array('message' => '课次未开始或已下课，不能结束活动。'));
        }

        $courseMember = $this->getCourseMemberService()->getCourseMember($task['courseId'], $user['id']);

        if ($courseMember['role'] != 'teacher') {
            return $this->createJsonResponse(array('message' => '你不是老师，不能结束活动。'));
        }

        $status = $this->getStatusService()->endTask($taskId, $activityId);

        return $this->createJsonResponse(array('status' => $status['status']));
    }

    public function resultAction($taskId)
    {
        $user = $this->getCurrentUser();
        $avatar = $this->get('web.twig.app_extension')->userAvatar($user);
        $answer = array(
            'truename' => $user['truename'],
            'avatar' => empty($avatar) ? null : $this->getWebExtension()->getFpath($avatar, 'avatar.png'),
            'content' => null,
        );
        $response = array();
        $results = $this->getResultService()->findResultsByTaskId($taskId);
        $userIds = ArrayToolkit::column($results, 'userId');
        $users = $this->getUserService()->findUsersByIds($userIds);
        foreach ($results as $key => $result) {
            if ($result['userId'] == $user['id']) {
                $answer['resultId'] = $result['id'];
                $answer['content'] = $result['content'];
                $answer['createdTime'] = $result['createdTime'];
                continue;
            }
            $avatar = $this->get('web.twig.app_extension')->userAvatar($users[$result['userId']], 'small');
            $response[] = array(
                'truename' => $users[$result['userId']]['truename'],
                'avatar' => empty($avatar) ? null : $this->getWebExtension()->getFpath($avatar, 'avatar.png'),
                'resultId' => $result['id'],
                'groupId' => $result['groupId'],
                'replyCount' => $result['replyCount'],
                'content' => $result['content'],
                'createdTime' => $result['createdTime'],
            );
        }

        $task = $this->getTaskService()->getTask($taskId);
        $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($taskId);
        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonTask['lessonId']);
        if ($lesson['status'] == 'teaching') {
            $groups = $this->getCourseGroupService()->findCourseGroupsByCourseIdWithMembers($task['courseId']);
            $groupIds = ArrayToolkit::column($groups, 'id');
        } else {
            $groupIds = array_keys($results);
        }
        $response = ArrayToolkit::group($response, 'groupId');
        $isGrouped = count($groupIds) > 1;
        if ($isGrouped) {
            foreach ($response as $key => $value) {
                unset($response[$key]);
                $response[$key]['replyCount'] = empty(reset($value)['replyCount']) ? 0 : reset($value)['replyCount'];
                $response[$key]['currentReplyCount'] = count($value);
                $response[$key]['replys'] = $value;
            }
        }

        $status = $this->getStatusService()->getStatusByTaskId($taskId);
        return $this->createJsonResponse(array(
            'answer' => $answer,
            'isAnswer' => !empty($answer['content']),
            'status' => $status['status'],
            'isGrouped' => $isGrouped,
            'results' => array_values($response),
        ));
    }

    protected function getStatusService()
    {
        return $this->createService('CustomBundle:Task:TaskStatusService');
    }

    protected function getCourseMemberService()
    {
        return $this->createService('CustomBundle:Course:MemberService');
    }

    protected function getGroupMemberService()
    {
        return $this->createService('CustomBundle:Course:GroupMemberService');
    }

    protected function getCourseLessonService()
    {
        return $this->createService('CustomBundle:Course:CourseLessonService');
    }

    protected function getResultService()
    {
        return $this->createService('CustomBundle:Activity:OneSentenceResultService');
    }

    protected function getCourseGroupService()
    {
        return $this->createService('CustomBundle:Course:CourseGroupService');
    }

    protected function getTaskService()
    {
        return $this->createService('CustomBundle:Task:TaskService');
    }

    protected function getActivityService()
    {
        return $this->createService('Activity:ActivityService');
    }
}
