<?php
namespace CustomBundle\Biz\Questionnaire\Dao\Impl;

use CustomBundle\Biz\Questionnaire\Dao\QuestionDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class QuestionDaoImpl extends GeneralDaoImpl implements QuestionDao
{
    protected $table = 'questionnaire_question';

    public function findQuestionsByQuestionnaireId($questionnaireId, $start, $limit)
    {
        $sql = "SELECT * FROM {$this->table} where questionnaireId = ? ORDER BY seq ASC LIMIT {$start}, {$limit}";

        return $this->db()->fetchAll($sql, array($questionnaireId)) ?: array();
    }

    public function findQuestionsByIds($ids)
    {
        return $this->findInField('id', $ids);
    }

    public function deleteByIds($ids)
    {
        $marks = str_repeat('?,', count($ids) - 1).'?';
        $sql = "DELETE FROM {$this->table} WHERE id IN ({$marks})";

        return $this->db()->executeUpdate($sql, $ids);
    }

    public function deleteByQuestionnaireId($questionnaireId)
    {
        $sql = "DELETE FROM {$this->table} WHERE questionnaireId = ?";

        return $this->db()->executeUpdate($sql, array($questionnaireId));
    }

    public function getMaxSeqByQuestionnaireId($questionnaireId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE `questionnaireId` = ? ORDER BY `seq` DESC LIMIT 1";
        return $this->db()->fetchAssoc($sql, array($questionnaireId)) ?: array();
    }

    public function declares()
    {
        return array(
            'serializes' => array('metas' => 'json'),
            'orderbys'   => array('createdTime'),
            'timestamps' => array('createdTime', 'updatedTime'),
            'conditions' => array(
                'id = :id',
                'type = :type',
                'stem = :stem',
                'metas = :metas',
                'seq = :seq',
                'questionnaireId = :questionnaireId',
            ),
        );
    }
}
