<?php

namespace Biz\System\Dao\Impl;

use Biz\System\Dao\LogDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class LogDaoImpl extends GeneralDaoImpl implements LogDao
{
    protected $table = 'log';

    public function declares()
    {
        return array(
            'orderbys' => array(
                'createdTime',
                'id',
            ),
            'conditions' => array(
                'module = :module',
                'action = :action',
                'level = :level',
                'userId = :userId',
                'createdTime > :startDateTime',
                'createdTime < :endDateTime',
                'createdTime >= :startDateTime_GE',
                'userId IN ( :userIds )',
            ),
        );
    }

    public function analysisLoginNumByTime($startTime, $endTime)
    {
        $sql = "SELECT count(distinct userid)  as num FROM `{$this->table}` WHERE `action`='login_success' AND  `createdTime`>= ? AND `createdTime`<= ?  ";

        return $this->db()->fetchColumn($sql, array($startTime, $endTime));
    }

    public function analysisLoginDataByTime($startTime, $endTime)
    {
        $sql = "SELECT count(distinct userid) as count, from_unixtime(createdTime,'%Y-%m-%d') as date FROM `{$this->table}` WHERE `action`='login_success' AND `createdTime`>= ? AND `createdTime`<= ? group by from_unixtime(`createdTime`,'%Y-%m-%d') order by date ASC ";

        return $this->db()->fetchAll($sql, array($startTime, $endTime));
    }
}
