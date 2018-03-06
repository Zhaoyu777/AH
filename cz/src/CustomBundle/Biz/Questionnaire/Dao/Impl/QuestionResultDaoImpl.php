<?php
namespace CustomBundle\Biz\Questionnaire\Dao\Impl;

use CustomBundle\Biz\Questionnaire\Dao\QuestionResultDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class QuestionResultDaoImpl extends GeneralDaoImpl implements QuestionResultDao
{
    protected $table = 'questionnaire_question_result';

    public function findQuestionResultsByQuestionnaireResultIds($questionnaireResultIds)
    {
        return $this->findInField('questionnaireResultId', $questionnaireResultIds);
    }

    public function deleteQuestionResultsByQuestionnaireResultIds($questionnaireResultIds)
    {
        $marks = str_repeat('?,', count($questionnaireResultIds) - 1).'?';
        $sql = "DELETE FROM {$this->table} WHERE questionnaireResultId IN ({$marks})";

        return $this->db()->executeUpdate($sql, $questionnaireResultIds);
    }

    public function deleteQuestionResultsByQuestionId($questionId)
    {
        return $this->db()->delete($this->table(), array('questionId' => $questionId));
    }

    public function declares()
    {
        return array(
            'serializes' => array('answer' => 'json'),
            'orderbys'   => array('createdTime'),
            'timestamps' => array('createdTime', 'updatedTime'),
            'conditions' => array(
                'id = :id',
                'questionnaireResultId = :questionnaireResultId',
                'questionId = :questionId',
                'answer = :answer',
            ),
        );
    }
}
