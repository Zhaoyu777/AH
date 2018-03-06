<?php

namespace CustomBundle\Biz\System\Service\Impl;

use AppBundle\Common\ArrayToolkit;
use CustomBundle\Biz\System\Service\StatisticsService;
use Biz\System\Service\Impl\StatisticsServiceImpl as BaseStatisticsServiceImpl;

class StatisticsServiceImpl extends BaseStatisticsServiceImpl implements StatisticsService
{
    public function countTeacherOnline($retentionTime)
    {
        return $this->getSessionDao()->countTeacherOnline($retentionTime);
    }

    public function countStudentOnline($retentionTime)
    {
        return $this->getSessionDao()->countStudentOnline($retentionTime);
    }

    protected function getSessionDao()
    {
        return $this->createDao('CustomBundle:System:SessionDao');
    }
}
