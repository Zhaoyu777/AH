<?php
namespace CustomBundle\Biz\Questionnaire\Dao;

interface QuestionResultDao
{
    public function findQuestionResultsByQuestionnaireResultIds($questionnaireResultIds);

    public function deleteQuestionResultsByQuestionnaireResultIds($questionnaireResultIds);

    public function deleteQuestionResultsByQuestionId($questionId);
}
