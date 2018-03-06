<?php

namespace CustomBundle\Biz\System\Service;

use Biz\System\Service\StatisticsService as BaseStatisticsService;

interface StatisticsService extends BaseStatisticsService
{
    public function countTeacherOnline($retentionTime);

    public function countStudentOnline($retentionTime);
}
