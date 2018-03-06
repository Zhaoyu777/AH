<?php
namespace CustomBundle\Biz\Questionnaire\Dao\Impl;

use CustomBundle\Biz\Questionnaire\Dao\QuestionnaireDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class QuestionnaireDaoImpl extends GeneralDaoImpl implements QuestionnaireDao
{
    protected $table = 'questionnaire';

    public function findQuestionnaireByIds($ids)
    {
        return $this->findInField('id', $ids);
    }

    public function deleteByIds($ids)
    {
        $marks = str_repeat('?,', count($ids) - 1).'?';
        $sql = "DELETE FROM {$this->table} WHERE id IN ({$marks})";

        return $this->db()->executeUpdate($sql, $ids);
    }

    public function findQuestionnairesByCourseSetId($courseSetId)
    {
        return $this->findByFields(array('courseSetId' => $courseSetId));
    }

    public function findQuestionnairesNotNullByCourseSetId($courseSetId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE `courseSetId` = ? AND `itemCount` > 0;";

        return $this->db()->fetchAll($sql, array($courseSetId)) ?: array();
    }

    public function declares()
    {
        return array(
            'serializes' => array('metas' => 'json'),
            'orderbys'   => array('createdTime'),
            'timestamps' => array('createdTime', 'updatedTime'),
            'conditions' => array(
                'id = :id',
                'courseSetId = :courseSetId',
                'description = :description',
                'updatedUserId = :updatedUserId',
                'title = :title',
                'itemCount = :itemCount',
            ),
        );
    }
}
