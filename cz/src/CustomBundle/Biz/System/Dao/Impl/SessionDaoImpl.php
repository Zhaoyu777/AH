<?php

namespace CustomBundle\Biz\System\Dao\Impl;

use CustomBundle\Biz\System\Dao\SessionDao;
use Biz\System\Dao\Impl\SessionDaoImpl as BaseSessionDaoImpl;

class SessionDaoImpl extends BaseSessionDaoImpl implements SessionDao
{
    public function countTeacherOnline($retentionTime)
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} LEFT JOIN `user` on user.id = {$this->table}.sess_user_id WHERE `sess_time`  >= (unix_timestamp(now()) - ?) AND user.roles LIKE '%ROLE_TEACHER%';";

        return $this->db()->fetchColumn($sql, array($retentionTime)) ? : 0;
    }

    public function countStudentOnline($retentionTime)
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} LEFT JOIN `user` on user.id = {$this->table}.sess_user_id WHERE `sess_time`  >= (unix_timestamp(now()) - ?) AND user.roles NOT LIKE '%ROLE_TEACHER%';";

        return $this->db()->fetchColumn($sql, array($retentionTime)) ? : 0;
    }
}
