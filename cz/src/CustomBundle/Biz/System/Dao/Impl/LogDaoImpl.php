<?php

namespace CustomBundle\Biz\System\Dao\Impl;

use CustomBundle\Biz\System\Dao\LogDao;
use Biz\System\Dao\Impl\LogDaoImpl as BaseLogDaoImpl;

class LogDaoImpl extends BaseLogDaoImpl implements LogDao
{
    public function countByTimeRangeAndOrgId($timeRange, $orgCode)
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} ";
        $sql .= "LEFT JOIN user ON {$this->table}.userId = user.id WHERE ";

        $sql .= "{$this->table}.createdTime >= (unix_timestamp(now()) - ?) AND {$this->table}.action = 'start_course_lesson' AND user.orgCode LIKE ?;";

        return $this->db()->fetchColumn($sql, array($timeRange, $orgCode)) ? : 0;
    }

    public function findByTime($startTime, $endTime)
    {
        $sql = "SELECT userId FROM {$this->table} ";
        $sql .= "LEFT JOIN user ON userId = user.id WHERE ";
        $sql .= "roles LIKE '%ROLE_TEACHER%' AND `action`='login_success' AND  {$this->table}.createdTime >= ? AND {$this->table}.createdTime <= ? group by userId";

        return $this->db()->fetchAll($sql, array($startTime, $endTime));
    }
}
