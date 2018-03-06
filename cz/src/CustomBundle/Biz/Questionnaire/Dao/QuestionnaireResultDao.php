<?php

namespace CustomBundle\Biz\Questionnaire\Dao;

interface QuestionnaireResultDao
{
    public function getUserUnfinishResult($questionnaireId, $userId);

    public function getQuestionnaireResultByQuestionnaireIdAndTaskIdAndUserId($questionnaireId, $taskId, $userId);

    public function findQuestionnaireResultsByQuestionnaireId($questionnaireId);

    public function findQuestionnaireResultsByTaskIds($taskIds);

    public function deleteQuestionnaireResultsByTaskIds($taskIds);

    public function findQuestionnaireResultsByQuestionnaireIdAndStatus($questionnaireId, $status);

    public function findByTaskId($taskId);
}
