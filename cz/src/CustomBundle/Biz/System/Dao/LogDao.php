<?php

namespace CustomBundle\Biz\System\Dao;

use Biz\System\Dao\LogDao as BaseLogDao;

interface LogDao extends BaseLogDao
{
    public function countByTimeRangeAndOrgId($timeRange, $orgCode);
}
