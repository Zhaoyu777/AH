<?php

namespace CustomBundle\Biz\System\Service\Impl;

use AppBundle\Common\ArrayToolkit;
use CustomBundle\Biz\System\Service\LogService;
use Biz\System\Service\Impl\LogServiceImpl as BaseLogServiceImpl;

class LogServiceImpl extends BaseLogServiceImpl implements LogService
{
    public function countRecordsByTimeRangeAndOrgId($timeRange, $orgId)
    {
        return $this->getLogDao()->countByTimeRangeAndOrgId($timeRange, $orgId);
    }

    public function findLogByTime($startTime, $endTime)
    {
        return $this->getLogDao()->findByTime($startTime, $endTime);
    }

    protected function getLogDao()
    {
        return $this->createDao('CustomBundle:System:LogDao');
    }
}
