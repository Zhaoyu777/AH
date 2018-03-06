<?php
namespace CustomBundle\Biz\Questionnaire\Service\Impl;

use Biz\BaseService;
use AppBundle\Common\ArrayToolkit;
use CustomBundle\Biz\Questionnaire\Service\QuestionService;

class QuestionServiceImpl extends BaseService implements QuestionService
{
    public function getQuestion($id)
    {
        return $this->getQuestionDao()->get($id);
    }

    public function createQuestion($fields)
    {
        $question = ArrayToolkit::parts($fields, array(
            'stem',
            'metas',
            'type',
            'questionnaireId',
        ));

        $maxSeqQuestion = $this->getQuestionDao()->getMaxSeqByQuestionnaireId($question['questionnaireId']);
        $seq = empty($maxSeqQuestion) ? 0:$maxSeqQuestion['seq'];
        $question['seq'] = $seq + 1;
        $createQuestion = $this->getQuestionDao()->create($question);

        $questionCount = $this->getQuestionDao()->count(array('questionnaireId' => $createQuestion['questionnaireId']));
        $this->getQuestionnaireService()->updateQuestionnaire($createQuestion['questionnaireId'], array('itemCount' => $questionCount));

        return $createQuestion;
    }

    public function createQuestionResult($fields)
    {
        return $this->getQuestionResultDao()->create($fields);
    }

    public function updateQuestion($id, $fields)
    {
        $fields = ArrayToolkit::parts($fields, array(
            'stem',
            'metas',
            'seq',
        ));
        return $this->getQuestionDao()->update($id, $fields);
    }

    public function deleteQuestion($id)
    {
        $question = $this->getQuestion($id);
        $this->deleteQuestionResultsByQuestionId($id);
        $questionCount = $this->findQuestionsCountByQuestionnaireId($question['questionnaireId']);
        if ($questionCount <= 1) {
             throw $this->createAccessDeniedException("至少保留一个试题");
        }

        $result = $this->getQuestionDao()->delete($id);
        $questionCount = $this->findQuestionsCountByQuestionnaireId($question['questionnaireId']);
        $this->getQuestionnaireService()->updateQuestionnaireItemCount($question['questionnaireId'], $questionCount);

        $questions = $this->findQuestionsByQuestionnaireId($question['questionnaireId'], 0, PHP_INT_MAX);
        $this->sortQuestions($question['questionnaireId'], ArrayToolkit::column($questions, 'id'));

        return $result;
    }

    public function deleteQuestions($questionnaireId, $ids)
    {
        if (empty($ids)) {
            return;
        }
        $this->getQuestionDao()->deleteByIds($ids);
        $questionCount = $this->findQuestionsCountByQuestionnaireId($questionnaireId);
        $this->getQuestionnaireService()->updateQuestionnaireItemCount($questionnaireId, $questionCount);

        return;
    }

    public function deleteQuestionResultsByQuestionId($questionId)
    {
        return $this->getQuestionResultDao()->deleteQuestionResultsByQuestionId($questionId);
    }

    public function deleteQuestionsByQuestionnaireId($questionnaireId)
    {
        return $this->getQuestionDao()->deleteByQuestionnaireId($questionnaireId);
    }

    public function deleteQuestionResultsByQuestionnaireResultIds($questionnaireResultIds)
    {
        return $this->getQuestionResultDao()->deleteQuestionResultsByQuestionnaireResultIds($questionnaireResultIds);
    }

    public function copyQuestion($fromQuestionnaireId, $toQuestionnaireId)
    {
        $questions = $this->findQuestionsByQuestionnaireId($fromQuestionnaireId, 0, PHP_INT_MAX);

        foreach ($questions as $key => $question) {
            $question['questionnaireId'] = $toQuestionnaireId;
            unset($question['id']);
            $this->getQuestionDao()->create($question);
        }

        return true;
    }

    public function findQuestionResultsByQuestionnaireResultIds($questionnaireResultIds)
    {
        return $this->getQuestionResultDao()->findQuestionResultsByQuestionnaireResultIds($questionnaireResultIds);
    }

    public function findQuestionsByQuestionnaireId($questionnaireId, $start, $limit)
    {
        return $this->getQuestionDao()->findQuestionsByQuestionnaireId($questionnaireId, $start, $limit);
    }

    public function findQuestionsCountByQuestionnaireId($questionnaireId)
    {
        return $this->getQuestionDao()->count(array('questionnaireId' => $questionnaireId));
    }

    public function searchQuestions($conditions, $orderBy, $start, $limit)
    {
        return $this->getQuestionDao()->search($conditions, $orderBy, $start, $limit);
    }

    public function searchQuestionsCount($conditions)
    {
        return $this->getQuestionDao()->count($conditions);
    }

    public function findQuestionsByIds($ids)
    {
        if (empty($ids)) {
            return array();
        }
        return ArrayToolkit::index($this->getQuestionDao()->findQuestionsByIds($ids), 'id');
    }

    public function sortQuestions($questionnaireId, $ids)
    {
        $seq = 0;
        foreach ($ids as $id) {
            ++$seq;
            $fields = array('seq' => $seq);
            $question = $this->getQuestion($id);
            if ($fields['seq'] != $question['seq']) {
                $this->updateQuestion($question['id'], $fields);
            }
        }
    }

    protected function getQuestionnaireService()
    {
        return $this->createService('CustomBundle:Questionnaire:QuestionnaireService');
    }

    protected function getQuestionDao()
    {
        return $this->createDao('CustomBundle:Questionnaire:QuestionDao');
    }

    protected function getQuestionResultDao()
    {
        return $this->createDao('CustomBundle:Questionnaire:QuestionResultDao');
    }
}
