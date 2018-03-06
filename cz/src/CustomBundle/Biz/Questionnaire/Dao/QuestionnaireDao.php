<?php
namespace CustomBundle\Biz\Questionnaire\Dao;

interface QuestionnaireDao
{
    public function findQuestionnaireByIds($ids);

    public function deleteByIds($ids);

    public function findQuestionnairesByCourseSetId($courseSetId);
}
