<?php

namespace CustomBundle\Biz\RandomTestpaper\Dao\Impl;

use CustomBundle\Biz\RandomTestpaper\Dao\RandomTestpaperDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class RandomTestpaperDaoImpl extends GeneralDaoImpl implements RandomTestpaperDao
{
    protected $table = 'random_testpaper';

    public function getLastTestpaperByTaskIdAndUserId($taskId, $userId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE `taskId` = ? AND `userId` = ? ORDER BY `doTime` DESC LIMIT 1";

        return $this->db()->fetchAssoc($sql, array($taskId, $userId)) ?: null;
    }

    public function findByTaskIdAndDoTime($taskId, $doTime)
    {
        $sql = "SELECT * FROM {$this->table} WHERE `taskId` = ? AND `doTime` = ? ORDER BY createdTime DESC;";

        return $this->db()->fetchAll($sql, array($taskId, $doTime)) ? : array();
    }

    public function findMaxScoreAndTimsByTaskIdGroupByUserId($taskId)
    {
        $sql = "SELECT userId, max(score) as score,max(doTime) as doTimes FROM {$this->table} WHERE `taskId` = ? GROUP BY `userId`;";

        return $this->db()->fetchAll($sql, array($taskId)) ? : array();
    }

    public function declares()
    {
        return array(
            'serializes' => array(),
            'orderbys'   => array(),
            'timestamps' => array('createdTime', 'updatedTime'),
            'conditions' => array(
                'createdTime >= :raceCreatedTime',
            ),
        );
    }
}
