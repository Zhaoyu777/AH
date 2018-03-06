<?php

namespace CustomBundle\Biz\Activity\Dao\Impl;

use CustomBundle\Biz\Activity\Dao\PracticeWorkResultDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class PracticeWorkResultDaoImpl extends GeneralDaoImpl implements PracticeWorkResultDao
{
    protected $table = 'practice_work_result';

    public function getByTaskIdAndUserId($taskId, $userId)
    {
        return $this->getByFields(array('taskId' => $taskId, 'userId' => $userId));
    }

    public function findResultByPracticeWorkIds($practiceWorkIds)
    {
        return $this->findInField('practiceWorkId', $practiceWorkIds);
    }

    public function findResultsStatusNumGroupByStatus($practiceWorkId)
    {
        $sql = "SELECT status,COUNT(id) AS num FROM {$this->table} WHERE practiceWorkId=? GROUP BY status";

        return $this->db()->fetchAll($sql, array($practiceWorkId)) ?: array();
    }

    public function getLastResultByPracticeWorkId($practiceWorkId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE `practiceWorkId` = ? ORDER BY `finalSubTime` DESC LIMIT 1";

        return $this->db()->fetchAssoc($sql, array($practiceWorkId)) ?: null;
    }

    public function findByTaskIds($taskIds)
    {
        return $this->findInField('taskId', $taskIds);
    }

    public function findPracticeWorkResultsByPracticeWorkId($practiceWorkId)
    {
        return $this->findByFields(array('practiceWorkId' => $practiceWorkId));
    }

    public function declares()
    {
        return array(
            'serializes' => array(),
            'orderbys'   => array('createdTime', 'updatedTime'),
            'timestamps' => array('createdTime', 'updatedTime'),
            'conditions' => array(
                'taskId =:taskId',
                'id =:id',
                'userId =:userId',
                'activityId =:activityId',
                'status =:status',
                'status !=:notFinishedStatus',
                'practiceWorkId =:practiceWorkId',
                'comment =:comment',
                'appraisal =:appraisal',
                'practiceWorkId IN (:practiceWorkIds)',
            )
        );
    }
}
