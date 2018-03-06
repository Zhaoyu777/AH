<?php
namespace CustomBundle\Biz\Questionnaire\Service;

interface QuestionService
{
    public function getQuestion($id);

    public function createQuestion($fields);

    public function createQuestionResult($fields);

    public function updateQuestion($id, $fields);

    public function deleteQuestion($id);

    public function deleteQuestions($questionnaireId, $ids);

    public function deleteQuestionsByQuestionnaireId($questionnaireId);

    public function deleteQuestionResultsByQuestionnaireResultIds($questionnaireResultIds);

    public function deleteQuestionResultsByQuestionId($questionId);

    public function findQuestionsByQuestionnaireId($questionnaireId, $start, $limit);

    public function findQuestionsCountByQuestionnaireId($questionnaireId);

    public function searchQuestions($conditions, $orderBy, $start, $limit);

    public function searchQuestionsCount($conditions);

    public function sortQuestions($questionnaireId, $ids);

    public function findQuestionsByIds($ids);

    public function findQuestionResultsByQuestionnaireResultIds($questionnaireResultIds);
}
