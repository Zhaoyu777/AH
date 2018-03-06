<?php

namespace CustomBundle\Biz\System\Service;

use Biz\System\Service\LogService as BaseLogService;

interface LogService extends BaseLogService
{
    public function countRecordsByTimeRangeAndOrgId($timeRange, $orgId);

    public function findLogByTime($startTime, $endTime);
}
