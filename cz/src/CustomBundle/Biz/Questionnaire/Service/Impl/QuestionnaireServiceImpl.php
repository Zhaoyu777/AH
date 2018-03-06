<?php

namespace CustomBundle\Biz\Questionnaire\Service\Impl;

use Biz\BaseService;
use AppBundle\Common\ArrayToolkit;
use Topxia\Service\Common\ServiceKernel;
use Codeages\Biz\Framework\Event\Event;
use CustomBundle\Biz\Questionnaire\Service\QuestionnaireService;

class QuestionnaireServiceImpl extends BaseService implements QuestionnaireService
{
    public function getQuestionnaire($id)
    {
        return $this->getQuestionnaireDao()->get($id);
    }

    public function createQuestionnaire($fields)
    {
        $questionnaire = ArrayToolkit::parts($fields, array(
            'title',
            'courseSetId',
            'updatedUserId',
            'description',
        ));

        return $this->getQuestionnaireDao()->create($questionnaire);
    }

    public function updateQuestionnaire($id, $fields)
    {
        $questionnaire = ArrayToolkit::parts($fields, array(
            'title',
            'description',
            'updatedUserId',
            'itemCount',
        ));

        return $this->getQuestionnaireDao()->update($id, $questionnaire);
    }

    public function updateQuestionnaireItemCount($id, $count)
    {
        return $this->getQuestionnaireDao()->update($id, array('itemCount' => $count));
    }

    public function deleteQuestionnaire($id)
    {
        $this->getQuestionService()->deleteQuestionsByQuestionnaireId($id);

        return $this->getQuestionnaireDao()->delete($id);
    }

    public function deleteQuestionnaires($ids)
    {
        if (empty($ids)) {
            return;
        }

        foreach ($ids as $id) {
            $this->getQuestionService()->deleteQuestionsByQuestionnaireId($id);
        }

        return $this->getQuestionnaireDao()->deleteByIds($ids);
    }

    public function copyQuestionnaire($id, $courseSetId)
    {
        $fields = $this->getQuestionnaire($id);
        $fields['courseSetId'] = $courseSetId;
        unset($fields['id']);
        $questionnaire = $this->createQuestionnaire($fields);
        $this->getQuestionService()->copyQuestion($id, $questionnaire['id']);

        return $questionnaire;
    }

    public function findQuestionnairesByCourseSetId($courseSetId)
    {
        return $this->getQuestionnaireDao()->findQuestionnairesByCourseSetId($courseSetId);
    }

    public function findQuestionnairesNotNullByCourseSetId($courseSetId)
    {
        return $this->getQuestionnaireDao()->findQuestionnairesNotNullByCourseSetId($courseSetId);
    }

    public function searchQuestionnaires($conditions, $orderBy, $start, $limit)
    {
        return $this->getQuestionnaireDao()->search($conditions, $orderBy, $start, $limit);
    }

    public function searchQuestionnaireCount($conditions)
    {
        return $this->getQuestionnaireDao()->count($conditions);
    }

    public function findQuestionnaireByIds($ids)
    {
        if (empty($ids)) {
            return array();
        }

        return $this->getQuestionnaireDao()->findQuestionnaireByIds($ids);
    }

    public function startQuestionnaire($id, $fields)
    {
        if (!ArrayToolkit::requireds($fields, array('taskId', 'activityId'))) {
            throw $this->createInvalidArgumentException(' Invalid Argument');
        }

        $questionnaire = $this->getQuestionnaire($id);
        $user = $this->getCurrentUser();

        $questionnaireResult = $this->getQuestionnaireResultByQuestionnaireIdAndTaskIdAndUserId($questionnaire['id'], $fields['taskId'], $user['id']);

        if (!$questionnaireResult) {
            $fields = array(
                'questionnaireId' => $questionnaire['id'],
                'userId' => $user['id'],
                'status' => 'doing',
                'taskId' => empty($fields['taskId']) ? 0 : $fields['taskId'],
                'activityId' => empty($fields['activityId']) ? 0 : $fields['activityId'],
            );

            $questionnaireResult = $this->addQuestionnaireResult($fields);
        }

        return $questionnaireResult;
    }

    public function finishTest($resultId, $formData)
    {
        $result = $this->getQuestionnaireResult($resultId);
        $user = $this->getCurrentUser();

        if (empty($result)) {
            throw $this->createNotFoundException('该调查结果不存在！');
        }
        $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($result['taskId']);
        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonTask['lessonId']);
        $taskStatus = $this->getTaskStatusService()->getStatusByTaskId($result['taskId']);

        if ($lessonTask['stage'] == 'in' && $lesson['status'] != 'teaching') {
            throw $this->createNotFoundException('课程未开始！');
        }

        if ($lessonTask['stage'] == 'in' && $taskStatus['status'] == 'end') {
            throw $this->createNotFoundException('活动已结束！');
        } elseif ($lessonTask['stage'] == 'in' && $taskStatus['status'] == '') {
            throw $this->createNotFoundException('活动未开始！');
        }

        if ($result['userId'] != $user['id']) {
            throw $this->createAccessDeniedException($this->getKernel()->trans('无权修改其他学员的调查问卷！'));
        }

        if ($result['status'] == 'finished') {
            throw $this->createServiceException($this->getKernel()->trans('已经交卷的调查不能修改!'));
        }
        $answers = empty($formData['data']) ? array() : $formData['data'];

        $this->submitAnswers($result, $answers);
        $questionnaireResult = $this->getQuestionnaireResultDao()->update($result['id'], array('status' => 'finished'));

        //$this->dispatchEvent('questionnaire.finished', new Event(array('taskId' => $result['taskId'], 'questionnaireId' => $result['questionnaireId'])));

        $taskResult = $this->getTaskResultService()->getUserTaskResultByTaskId($questionnaireResult['taskId']);
        $taskResult = $this->getTaskService()->finishTaskResult($questionnaireResult['taskId']);
        //$this->dispatchEvent('course.task.finish', new Event($taskResult, array('user' => $this->getCurrentUser())));

        return $questionnaireResult;
    }

    protected function submitAnswers($result, $answers)
    {
        if (empty($answers)) {
            return array();
        }
        $user = $this->getCurrentUser();
        $questionIds = array_keys($answers);

        $questions = $this->getQuestionService()->findQuestionsByIds($questionIds);
        $sourceQuestionIds = ArrayToolkit::column($questions, 'id');

        if (array_diff($questionIds, $sourceQuestionIds)) {
            throw $this->createNotFoundException('该调查问题不存在！');
        }

        $this->beginTransaction();

        try {
            foreach ($answers as $questionId => $answer) {
                $question = empty($questions[$questionId]) ? array() : $questions[$questionId];

                $fields['questionnaireResultId'] = $result['id'];
                $fields['questionId'] = $questionId;
                $fields['answer'] = (array) $answer;

                $this->getQuestionService()->createQuestionResult($fields);
            }
            $this->commit();
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function canLookQuestionnaireResults($taskId, $questionnaireId)
    {
        $user = $this->getCurrentUser();

        if (!$user->isLogin()) {
            throw $this->createAccessDeniedException('未登录用户，无权操作！');
        }

        $questionnaire = $this->getQuestionnaire($questionnaireId);

        if (!$questionnaire) {
            throw $this->createNotFoundException($this->getKernel()->trans('调查问卷不存在!'));
        }

        $task = $this->getTaskService()->getTask($taskId);
        $member = $this->getCourseMemberService()->getCourseMember($task['courseId'], $user['id']);
        if ($member['role'] == 'teacher') {
            return true;
        }

        $questionnaireResult = $this->getQuestionnaireResultByQuestionnaireIdAndTaskIdAndUserId($questionnaireId, $taskId, $user['id']);

        $taskStatus = $this->getTaskStatusService()->getStatusByTaskId($task['id']);

        if ($questionnaireResult['userId'] == $user['id']) {
            return true;
        }

        return true;
    }

    public function getQuestionnaireResult($id)
    {
        return $this->getQuestionnaireResultDao()->get($id);
    }

    public function deleteQuestionnaireResultByTaskIds($taskIds)
    {
        if (empty($taskIds)) {
            return array();
        }
        $questionnaireResults = $this->findQuestionnaireResultsByTaskIds($taskIds);

        if (empty($questionnaireResults)) {
            return array();
        }
        $questionnaireResultIds = ArrayToolkit::column($questionnaireResults, 'id');
        $this->getQuestionService()->deleteQuestionResultsByQuestionnaireResultIds($questionnaireResultIds);

        return $this->getQuestionnaireResultDao()->deleteQuestionnaireResultsByTaskIds($taskIds);
    }

    public function findQuestionnaireResultsByTaskIds($taskIds)
    {
        return $this->getQuestionnaireResultDao()->findQuestionnaireResultsByTaskIds($taskIds);
    }

    public function addQuestionnaireResult($fields)
    {
        return $this->getQuestionnaireResultDao()->create($fields);
    }

    public function getQuestionnaireResultByQuestionnaireIdAndTaskIdAndUserId($questionnaireId, $taskId, $userId)
    {
        return $this->getQuestionnaireResultDao()->getQuestionnaireResultByQuestionnaireIdAndTaskIdAndUserId($questionnaireId, $taskId, $userId);
    }

    public function getQuestionnaireResultByTaskIdAndUserId($taskId, $userId)
    {
        return $this->getQuestionnaireResultDao()->getByTaskIdAndUserId($taskId, $userId);
    }

    public function findQuestionResultsByQuestionnaireIdAndTaskId($questionnaireId, $taskId)
    {
        $questions = $this->getQuestionService()->findQuestionsByQuestionnaireId($questionnaireId, 0, PHP_INT_MAX);

        $questionnaireResults = $this->findQuestionnaireResultsByQuestionnaireIdAndTaskIdAndStatus($questionnaireId, $taskId, 'finished');

        $questionnaireResults = ArrayToolkit::index($questionnaireResults, 'id');

        $userIds = ArrayToolkit::column($questionnaireResults, 'userId');
        $users = $this->getUserService()->findUsersByIds($userIds);

        $questionnaireResultIds = ArrayToolkit::column($questionnaireResults, 'id');
        $userAnswers = $this->getQuestionService()->findQuestionResultsByQuestionnaireResultIds($questionnaireResultIds);

        if (!empty($userAnswers)) {
            $userAnswers = ArrayToolkit::group($userAnswers, 'questionId');
        }

        foreach ($questions as &$question) {
            $choices = array();
            $answers = empty($userAnswers[$question['id']]) ? array() : $userAnswers[$question['id']];
            if (in_array($question['type'], array('single_choice', 'choice'))) {
                foreach ($answers as $answer) {
                    $choices = array_merge_recursive($choices, $answer['answer']);
                }
                $itemCount = array_count_values($choices);

                $items = array();
                foreach ($question['metas'] as $key => $value) {
                    $items[$key]['text'] = $value;
                    $items[$key]['num'] = empty($itemCount[$key]) ? 0 : $itemCount[$key];
                    $items[$key]['part'] = empty($questionnaireResults) ? 0 : round($items[$key]['num'] / count($questionnaireResults) * 100, 2);
                }
                $question['items'] = $items;
                unset($question['metas']);
            } else {
                foreach ($answers as $answer) {
                    if (empty($answer['answer'][0])) {
                        continue;
                    }
                    $questionnaireResult = $questionnaireResults[$answer['questionnaireResultId']];
                    $userId = $questionnaireResult['userId'];
                    $question['answers'][$userId]['content'] = $answer['answer'][0];
                    $question['answers'][$userId]['user'] = $users[$userId]['nickname'];
                }
            }
        }

        return $questions;
    }

    public function findQuestionnaireResultsByQuestionnaireId($questionnaireId)
    {
        return $this->getQuestionnaireResultDao()->findQuestionnaireResultsByQuestionnaireId($questionnaireId);
    }

    public function findQuestionnaireResultsByQuestionnaireIdAndStatus($questionnaireId, $status)
    {
        return $this->getQuestionnaireResultDao()->findQuestionnaireResultsByQuestionnaireIdAndStatus($questionnaireId, $status);
    }

    public function findQuestionnaireResultsByQuestionnaireIdAndTaskIdAndStatus($questionnaireId, $taskId, $status)
    {
        return $this->getQuestionnaireResultDao()->findByQuestionnaireIdAndTaskIdAndStatus($questionnaireId, $taskId, $status);
    }

    public function deleteDoingQuestionnaireByTaskIds($taskIds)
    {
        if (empty($taskIds)) {
            return array();
        }
        $questionnaireResults = $this->findQuestionnaireResultsByTaskIds($taskIds);
        foreach ($questionnaireResults as $questionnaireResult) {
            if ($questionnaireResult['status'] == 'doing') {
                $this->getQuestionnaireResultDao()->delete($questionnaireResult['id']);
            }
        }

        return true;
    }


    public function countResultByTaskId($taskId)
    {
        return $this->getQuestionnaireResultDao()->countByTaskId($taskId);
    }

    public function findResultByCourseTaskId($courseTaskId)
    {
        return $this->getQuestionnaireResultDao()->findByTaskId($courseTaskId);
    }

    protected function getQuestionnaireDao()
    {
        return $this->createDao('CustomBundle:Questionnaire:QuestionnaireDao');
    }

    protected function getQuestionnaireResultDao()
    {
        return $this->createDao('CustomBundle:Questionnaire:QuestionnaireResultDao');
    }

    protected function getUserService()
    {
        return $this->createService('CustomBundle:User:UserService');
    }

    protected function getCourseMemberService()
    {
        return $this->createService('Course:MemberService');
    }

    protected function getQuestionService()
    {
        return $this->createService('CustomBundle:Questionnaire:QuestionService');
    }

    protected function getTaskService()
    {
        return $this->createService('CustomBundle:Task:TaskService');
    }

    protected function getTaskStatusService()
    {
        return $this->createService('CustomBundle:Task:TaskStatusService');
    }

    protected function getTaskResultService()
    {
        return $this->createService('Task:TaskResultService');
    }

    protected function getCourseLessonService()
    {
        return $this->createService('CustomBundle:Course:CourseLessonService');
    }

    protected function getKernel()
    {
        return ServiceKernel::instance();
    }
}
