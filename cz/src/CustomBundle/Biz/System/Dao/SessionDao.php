<?php

namespace CustomBundle\Biz\System\Dao;

use Biz\System\Dao\SessionDao as BaseSessionDao;

interface SessionDao extends BaseSessionDao
{
    public function countTeacherOnline($retentionTime);

    public function countStudentOnline($retentionTime);
}
