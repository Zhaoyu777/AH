<?php

namespace CustomBundle\Controller\Weixin;

use CustomBundle\Common\WeixinClient;
use AppBundle\Common\CurlToolkit;
use AppBundle\Common\ArrayToolkit;
use Symfony\Component\HttpFoundation\Request;
use CustomBundle\Biz\Course\Service\Impl\MemberService;
use CustomBundle\Controller\WeixinController;
use CustomBundle\Controller\Weixin\WeixinBaseController;
use CustomBundle\Biz\Activity\Service\Impl\RollcallResultService;

class RollcallController extends WeixinBaseController
{
    public function randStudentAction(Request $request)
    {
        $result = $request->query->all();
        $taskId = $result['taskId'];
        $courseId = $result['courseId'];

        $results = $this->getRollcallResultService()->findResultsByTaskId($taskId);
        $userIds = ArrayToolkit::column($results, 'userId');

        $task = $this->getTaskService()->getTask($taskId);
        $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($taskId);

        $randomStudentIds = $this->getCourseMemberService()->findRandomStudentIdsByLessonId($lessonTask['lessonId'], $userIds, 20);
        $selectedStudentId = $randomStudentIds[array_rand($randomStudentIds, 1)];

        $randomStudents = $this->baseUserInfo($randomStudentIds);
        $randomStudent = $randomStudents[$selectedStudentId];
        if (empty($randomStudent['userId'])) {
            return $this->createJsonResponse(array('success'=>'error', 'message' =>'所有成员已被抽过。'));
        }

        $result = $this->createRollcallResult($taskId, $randomStudent, array_values($randomStudents));

        $url = "/weixin/index.html#/course/{$courseId}/lesson/{$lessonTask['lessonId']}/task/{$taskId}/activity/{$task['activityId']}/type/rollcall";

        $course    = $this->getCourseService()->getCourse($courseId);
        $courseSet = $this->getCourseSetService()->getCourseSet($course['courseSetId']);
        $this->weixinRollcallMessage($request, $randomStudent['number'], $courseSet['title'], $url);

        $randomStudent['isScore'] = false;
        $randomStudent['score'] = 0;
        $randomStudent['resultId'] = $result['id'];

        return $this->createJsonResponse($randomStudent);
    }

    protected function baseUserInfo($userIds)
    {
        $users = $this->getUserService()->findUsersByIds($userIds);

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

        return $result;
    }

    public function taskStatusAction(Request $request)
    {
        $request = $request->query->all();
        $taskId = $request['taskId'];
        $user = $this->getCurrentUser();

        $result = $this->getRollcallResultService()->getResultByTaskIdAndUserId($taskId, $user['id']);

        if (empty($result)) {
            $result = false;
        }

        return $this->createJsonResponse($result);
    }

    public function remarkAction(Request $request)
    {
        $request = $request->query->all();
        $resultId = $request['resultId'];
        $courseId = $request['courseId'];
        $user = $this->getCurrentUser();
        $rollcallResult = $this->getRollcallResultService()->getResult($resultId);

        if (empty($rollcallResult)) {
            return $this->createJsonResponse(false);
        }
        $remark = explode(',', $request['remark']);
        $fields = array(
            'score' => $request['score'],
            'remark' => $remark
        );
        if ($rollcallResult['score'] == 0) {
            $this->getRollcallResultService()->remarkResult($resultId, $fields);
        }

        return $this->createJsonResponse($request['score']);
    }

    protected function getUserInfo($userId)
    {
        $user = $this->getUserService()->getUser($userId);
        return array(
            'userId' => $user['id'],
            'truename' => $user['truename'],
            'nickname' => $user['nickname'],
            'number' => $user['number'],
            'avatar' => $this->getWebExtension()->getFpath($user['smallAvatar'], 'avatar.png')
        );
    }

    protected function weixinRollcallMessage($request, $toid, $courseSetTitle, $url)
    {
        $toids[] = $toid;
        $content = "课程《{$courseSetTitle}》正在进行点名答题,你以被老师点到，请准备作答!";
        $goto = urlencode($url);

        $articles = array(
            'title' => '有一个点名答题正在进行',
            'description' => $content,
            'url' => $this->generateUrl('weixin_redirect', array("goto" => $goto), true),
            'picurl' => '',
        );

        $client = $this->getPlatformClient();
        $client->sendNewsMessage($toids, $articles);
    }

    protected function createRollcallResult($taskId, $randomStudent, $students)
    {
        $task = $this->getTaskService()->getTask($taskId);

        return $this->getRollcallResultService()->createResult(array(
            'courseId' => $task['courseId'],
            'activityId' => $task['activityId'],
            'userId' => $randomStudent['userId'],
            'courseTaskId' => $task['id'],
            'selectUser' => $randomStudent,
            'students' => $students,
        ));
    }

    protected function getRollcallResultService()
    {
        return $this->createService('CustomBundle:Activity:RollcallResultService');
    }
}
