<?php
namespace CustomBundle\Controller\Weixin;

use CustomBundle\Controller\Weixin\WeixinBaseController;
use AppBundle\Common\ArrayToolkit;
use Symfony\Component\HttpFoundation\Request;

class QuestionnaireController extends WeixinBaseController
{
    public function showAction($taskId, $activityId)
    {
        $task = $this->getTaskService()->getTask($taskId);
        $activity = $this->getActivityService()->getActivity($task['activityId']);

        $config = $this->getActivityService()->getActivityConfig('questionnaire');
        $questionnaireActivity = $config->get($activity['mediaId']);
        $questionnaire = $this->getQuestionnaireService()->getQuestionnaire($questionnaireActivity['mediaId']);
        if (!$questionnaire) {
            return $this->createJsonResponse(array('message' => '该调查问卷已删除'));
        }
        $user = $this->getCurrentUser();
        $member = $this->getCourseMemberService()->getCourseMember($task['courseId'], $user['id']);
        if ($member['role'] == 'teacher') {
            $result = $this->buildFinishData($questionnaire, $task);
            return $this->createJsonResponse($result);
        }

        $questionnaireResult = $this->getQuestionnaireService()->startQuestionnaire($questionnaire['id'], array('taskId' => $task['id'], 'activityId' => $task['activityId']));

        $result = $this->buildQuestionnaireData($questionnaire, $questionnaireResult, $task);

        return $this->createJsonResponse($result);
    }

    public function finishAction(Request $request, $resultId)
    {
        $formData = $request->request->all();

        if (empty($formData['content'])) {
            return $this->createJsonResponse(array('message' => '还没填写调查问卷'));
        }
        $data['data'] = $formData['content'];
        $result = $this->getQuestionnaireService()->finishTest($resultId, $data);

        $questionnaireResult = $this->getQuestionnaireService()->getQuestionnaireResult($resultId);
        $questionnaire = $this->getQuestionnaireService()->getQuestionnaire($questionnaireResult['questionnaireId']);
        $task = $this->getTaskService()->getTask($questionnaireResult['taskId']);

        $result = $this->buildFinishData($questionnaire, $task);

        return $this->createJsonResponse($result);
    }

    protected function buildQuestionnaireData($questionnaire, $questionnaireResult, $task)
    {
        if ($questionnaireResult && $questionnaireResult['status'] == 'finished') {
            $result = $this->buildFinishData($questionnaire, $task);

            return $result;
        }
        $result = $this->buildStartData($questionnaire, $questionnaireResult, $task);

        return $result;
    }

    protected function buildFinishData($questionnaire, $task)
    {
        $questions = $this->getQuestionService()->findQuestionsByQuestionnaireId($questionnaire['id'], 0, PHP_INT_MAX);
        $questionResults = $this->getQuestionnaireService()->findQuestionResultsByQuestionnaireIdAndTaskId($questionnaire['id'], $task['id']);
        $questionnaireResults = $this->getQuestionnaireService()->findQuestionnaireResultsByQuestionnaireIdAndTaskIdAndStatus($questionnaire['id'], $task['id'], 'finished');

        $memberNum = $this->getCourseMemberService()->countMembers(array('courseId' => $task['courseId'], 'role' => 'student'));

        $taskStatus = $this->getStatusService()->getStatusByTaskId($task['id']);

        $result = array(
            'status' => 'finished',
            'taskStatus' => $taskStatus['status'],
            'questionResults' => $questionResults,
            'questionnaire' => $questionnaire,
            'actualNum' => count($questionnaireResults),
            'memberNum' => $memberNum,
            'questions' => $questions,
        );

        return $result;
    }

    protected function buildStartData($questionnaire, $questionnaireResult, $task)
    {
        $questions = $this->getQuestionService()->findQuestionsByQuestionnaireId($questionnaire['id'], 0, PHP_INT_MAX);

        foreach ($questions as &$question) {
            $items = array();
            foreach ($question['metas'] as $key => $value) {
                $items[$key]['text'] = $value;
            }
            $question['items'] = $items;
        }

        list($questionnaire, $questions) = $this->filterField($questionnaire, $questions);
        $taskStatus = $this->getStatusService()->getStatusByTaskId($task['id']);

        $result = array(
            'status' => 'start',
            'taskStatus' => $taskStatus['status'],
            'questions' => $questions,
            'questionnaire' => $questionnaire,
            'resultId' => $questionnaireResult['id'],
        );

        return $result;
    }

    protected function filterField($questionnaire, $questions)
    {
        $questionnaire = ArrayToolkit::filter(
            $questionnaire,
            array(
                'title' => '',
                'description' => '',
            )
        );

        foreach ($questions as &$question) {
            $question = ArrayToolkit::filter(
                $question,
                array(
                    'id' => '',
                    'type' => '',
                    'stem' => '',
                    'items' => array(),
                    'seq' => 0,
                )
            );
        }
        return array($questionnaire, $questions);
    }

    protected function getCourseMemberService()
    {
        return $this->createService('Course:MemberService');
    }

    protected function getStatusService()
    {
        return $this->createService('CustomBundle:Task:TaskStatusService');
    }

    protected function getCourseService()
    {
        return $this->createService('Course:CourseService');
    }

    protected function getTaskService()
    {
        return $this->createService('CustomBundle:Task:TaskService');
    }

    protected function getActivityService()
    {
        return $this->createService('Activity:ActivityService');
    }

    protected function getQuestionnaireService()
    {
        return $this->createService('CustomBundle:Questionnaire:QuestionnaireService');
    }

    protected function getQuestionService()
    {
        return $this->createService('CustomBundle:Questionnaire:QuestionService');
    }
}
