<?php

namespace CustomBundle\Biz\Questionnaire\Dao\Impl;

use CustomBundle\Biz\Questionnaire\Dao\QuestionnaireResultDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class QuestionnaireResultDaoImpl extends GeneralDaoImpl implements QuestionnaireResultDao
{
    protected $table = 'questionnaire_result';

    public function getUserUnfinishResult($questionnaireId, $userId)
    {
        return $this->getByFields(array(
            'questionnaireId' => $questionnaireId,
            'userId' => $userId,
            'status' => 'finished',
        ));
    }

    public function getQuestionnaireResultByQuestionnaireIdAndTaskIdAndUserId($questionnaireId, $taskId, $userId)
    {
        return $this->getByFields(array(
            'questionnaireId' => $questionnaireId,
            'taskId' => $taskId,
            'userId' => $userId,
        ));
    }

    public function getByTaskIdAndUserId($taskId, $userId)
    {
        return $this->getByFields(array(
            'taskId' => $taskId,
            'userId' => $userId,
        ));
    }

    public function getByCourseTaskId($courseTaskId)
    {
        return $this->findByFields(array('courseTaskId' => $courseTaskId));
    }

    public function findQuestionnaireResultsByQuestionnaireId($questionnaireId)
    {
        return $this->findByFields(array(
            'questionnaireId' => $questionnaireId,
        ));
    }

    public function findQuestionnaireResultsByQuestionnaireIdAndStatus($questionnaireId, $status)
    {
        return $this->findByFields(array(
            'questionnaireId' => $questionnaireId,
            'status' => $status,
        ));
    }

    public function findByQuestionnaireIdAndTaskIdAndStatus($questionnaireId, $taskId, $status)
    {
        return $this->findByFields(array(
            'questionnaireId' => $questionnaireId,
            'taskId' => $taskId,
            'status' => $status,
        ));
    }

    public function findQuestionnaireResultsByTaskIds($taskIds)
    {
        $marks = str_repeat('?,', count($taskIds) - 1).'?';
        $sql = "SELECT * FROM {$this->table} WHERE taskId IN ({$marks});";

        return $this->db()->fetchAll($sql, $taskIds);
    }

    public function countByTaskId($taskId)
    {
        $sql = "SELECT count(id) FROM {$this->table} WHERE `taskId` = ?";

        return $this->db()->fetchColumn($sql, array($taskId)) ?: 0;
    }

    public function deleteQuestionnaireResultsByTaskIds($taskIds)
    {
        $marks = str_repeat('?,', count($taskIds) - 1).'?';
        $sql = "DELETE FROM {$this->table} WHERE taskId IN ({$marks})";

        return $this->db()->executeUpdate($sql, $taskIds);
    }

    public function findByTaskId($taskId)
    {
        return $this->findByFields(array('taskId' => $taskId));
    }

    public function findByQuestionnaireResultId($questionnaireResultId)
    {
        return $this->findByFields(array(
            'questionnaireResultId' => $questionnaireResultId,
        ));
    }

    public function declares()
    {
        return array(
            'serializes' => array(),
            'orderbys' => array('createdTime'),
            'timestamps' => array('createdTime', 'updatedTime'),
            'conditions' => array(
                'id = :id',
                'questionnaireId = :questionnaireId',
                'userId = :userId',
                'taskId = :taskId',
                'activityId = :activityId',
                'status = :status',
            ),
        );
    }
}
