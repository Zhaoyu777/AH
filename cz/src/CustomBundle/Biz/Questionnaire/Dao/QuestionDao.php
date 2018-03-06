<?php
namespace CustomBundle\Biz\Questionnaire\Dao;

interface QuestionDao
{
    public function findQuestionsByQuestionnaireId($questionnaireId, $start, $limit);

    public function findQuestionsByIds($ids);

    public function deleteByIds($ids);

    public function deleteByQuestionnaireId($questionnaireId);

    public function getMaxSeqByQuestionnaireId($questionnaireId);
}