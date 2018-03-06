<?php
namespace CustomBundle\Controller\Weixin;

use Biz\User\CurrentUser;
use CustomBundle\Common\Platform\PlatformFactory;
use AppBundle\Common\ArrayToolkit;
use Biz\Role\Util\PermissionBuilder;
use AppBundle\Controller\BaseController;
use Symfony\Component\HttpFoundation\Request;

class WeixinBaseController extends BaseController
{
    protected $weixinEntry = "/weixin/index.html";

    protected function getPlatformClient()
    {
        $biz = $this->getBiz();

        return PlatformFactory::create($biz);
    }

    protected function currentTask($lessonItems, $teacherIds)
    {
        $taskResults = array();
        $items = array();
        foreach ($lessonItems as $lessonItem) {
            if ($lessonItem['type'] == 'lesson') {
                $items[$lessonItem['task']['id']] = $lessonItem['task'];
                $taskResult = $this->getTaskService()->findTaskResultsByUserIdsAndTaskId($teacherIds, $lessonItem['task']['id']);
                $taskResults = array_merge($taskResults, $taskResult);
            }
        }

        if (empty($taskResults)) {
            return array();
        }
        $task = $taskResults[0];
        foreach ($taskResults as $taskResult) {
            $task = $task['createdTime'] > $taskResult['createdTime'] ? $task : $taskResult;
        }

        return array(
            'taskId' => $items[$task['courseTaskId']]['id'],
            'activityId' => $items[$task['courseTaskId']]['activity']['id'],
            'activityTitle' => $items[$task['courseTaskId']]['activity']['title'],
            'activityType' => $items[$task['courseTaskId']]['type'],
        );
    }

    protected function baseUserInfo($ids)
    {
        if (!is_array($ids)) {
            $users[] = $this->getUserService()->getUser($ids);
        } else {
            $users = $this->getUserService()->findUsersByIds($ids);
        }

        $result = array();
        foreach ($users as $key => $user) {
            $result[$user['id']] = array(
                'userId' => $user['id'],
                'truename' => $user['truename'],
                'nickname' => $user['nickname'],
                'number' => $user['number'],
                'avatar' => $this->getWebExtension()->getFpath($user['smallAvatar'], 'avatar.png')
            );
        }

        if (!is_array($ids)) {
            return current($result);
        }

        return $result;
    }

    protected function getCourseMemberService()
    {
        return $this->createService('CustomBundle:Course:MemberService');
    }

    protected function getCourseService()
    {
        return $this->createService('CustomBundle:Course:CourseService');
    }

    protected function getCourseSetService()
    {
        return $this->createService('CustomBundle:Course:CourseSetService');
    }

    protected function getCourseLessonService()
    {
        return $this->createService('CustomBundle:Course:CourseLessonService');
    }

    protected function getTaskResultService()
    {
        return $this->createService('CustomBundle:Task:TaskResultService');
    }

    protected function getTaskService()
    {
        return $this->createService('CustomBundle:Task:TaskService');
    }
}
