<?php

namespace CustomBundle\Controller\Weixin;

use CustomBundle\Controller\Weixin\WeixinBaseController;
use AppBundle\Common\ArrayToolkit;
use Symfony\Component\HttpFoundation\Request;

class RandomTestpaperController extends WeixinBaseController
{
    public function resultAction($taskId)
    {
        $task = $this->getTaskService()->getTask($taskId);
        $user = $this->getCurrentUser();
        $courseMember = $this->getCourseMemberService()->getCourseMember($task['courseId'], $user['id']);
        $testpaper = $this->getRandomTestpaperService()->getLastTestpaperByTaskIdAndUserId($taskId, $user['id']);
        $builder = $this->getRandomTestpaperService()->getRandomTestpaperBuilder();
        if (!empty($testpaper)) {
            $questions = $builder->showTestItems($testpaper['id']);
            $accuracy = $builder->makeAccuracy($questions);
            $analysis = true;
        } else {
            $questions = $builder->buildItems($task['activityId']);
            $accuracy = array();
            $analysis = false;
        }

        if ($courseMember['role'] == 'teacher') {
            $analysis = false;
        }

        if (!empty($questions['fill'])) {
            foreach ($questions['fill'] as $key => &$question) {
                $question['stem'] = preg_split('/\[\[.+?\]\]/', $question['stem']);
            }
        }
        $questionIds = array();
        foreach ($questions as $type => $typeQuestions) {
            $questionIds = array_merge($questionIds, ArrayToolkit::column($typeQuestions, 'questionId'));
        }

        return $this->createJsonResponse(array(
            'analysis' => $analysis,
            'result' => $testpaper,
            'accuracy' => $accuracy,
            'questions' => $questions,
            'questionIds' => $questionIds,
        ));
    }

    public function redoAction($taskId)
    {
        $task = $this->getTaskService()->getTask($taskId);
        $builder = $this->getRandomTestpaperService()->getRandomTestpaperBuilder();
        $questions = $builder->buildItems($task['activityId']);
        if (!empty($questions['fill'])) {
            foreach ($questions['fill'] as $key => &$question) {
                $question['stem'] = preg_split('/\[\[.+?\]\]/', $question['stem']);
            }
        }

        $questionIds = array();
        foreach ($questions as $type => $typeQuestions) {
            $questionIds = array_merge($questionIds, ArrayToolkit::column($typeQuestions, 'questionId'));
        }

        return $this->createJsonResponse(array(
            'analysis' => false,
            'questions' => $questions,
            'questionIds' => $questionIds,
        ));
    }

    public function submitAction(Request $request, $taskId)
    {
        $questionIds = $request->request->get('questionIds');
        $answers = $request->request->get('data', array());
        $builder = $this->getRandomTestpaperService()->getRandomTestpaperBuilder();

        $testpaper = $builder->createTestpaper($taskId, $questionIds, $answers);
        $user = $this->getCurrentUser();
        $questions = $this->getRandomTestpaperService()->showTestpaperItems($taskId, $user['id']);
        if (!empty($questions['fill'])) {
            foreach ($questions['fill'] as $key => &$question) {
                $question['stem'] = preg_split('/\[\[.+?\]\]/', $question['stem']);
            }
        }
        $accuracy = $builder->makeAccuracy($questions);

        return $this->createJsonResponse(array(
            'questions' => $questions,
            'accuracy' => $accuracy,
            'result' => $testpaper,
        ));
    }

    protected function getCourseMemberService()
    {
        return $this->createService('CustomBundle:Course:MemberService');
    }

    protected function getTaskService()
    {
        return $this->createService('CustomBundle:Task:TaskService');
    }

    protected function getActivityService()
    {
        return $this->createService('Activity:ActivityService');
    }

    protected function getRandomTestpaperService()
    {
        return $this->createService('CustomBundle:RandomTestpaper:RandomTestpaperService');
    }

    protected function getUserService()
    {
        return $this->createService('User:UserService');
    }

    protected function getStatusService()
    {
        return $this->createService('CustomBundle:Task:TaskStatusService');
    }
}
