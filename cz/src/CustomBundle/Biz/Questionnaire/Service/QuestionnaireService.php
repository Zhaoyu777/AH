<?php

namespace CustomBundle\Biz\Questionnaire\Service;

interface QuestionnaireService
{
    public function getQuestionnaire($id);

    public function createQuestionnaire($fields);

    public function updateQuestionnaire($id, $fields);

    public function updateQuestionnaireItemCount($id, $count);

    public function deleteQuestionnaire($id);

    public function deleteQuestionnaires($ids);

    public function searchQuestionnaires($conditions, $orderBy, $start, $limit);

    public function searchQuestionnaireCount($conditions);

    public function findQuestionnaireByIds($ids);

    public function findQuestionnairesByCourseSetId($courseSetId);

    public function startQuestionnaire($id, $fields);

    public function addQuestionnaireResult($fields);

    public function getQuestionnaireResult($id);

    public function finishTest($resultId, $formData);

    public function canLookQuestionnaireResults($taskId, $questionnaireId);

    public function getQuestionnaireResultByQuestionnaireIdAndTaskIdAndUserId($questionnaireId, $taskId, $userId);

    public function findQuestionResultsByQuestionnaireIdAndTaskId($questionnaireId, $taskId);

    public function findQuestionnaireResultsByQuestionnaireId($questionnaireId);

    public function deleteQuestionnaireResultByTaskIds($taskIds);

    public function findQuestionnaireResultsByTaskIds($taskIds);

    public function deleteDoingQuestionnaireByTaskIds($taskIds);

    public function findQuestionnaireResultsByQuestionnaireIdAndStatus($questionnaireId, $status);

    public function findResultByCourseTaskId($courseTaskId);
}
