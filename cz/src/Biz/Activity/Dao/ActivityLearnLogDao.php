<?php

namespace Biz\Activity\Dao;

use Codeages\Biz\Framework\Dao\GeneralDaoInterface;

interface ActivityLearnLogDao extends GeneralDaoInterface
{
    public function getRecentFinishedLogByActivityIdAndUserId($activityId, $userId);

    public function countLearnedDaysByCourseIdAndUserId($courseId, $userId);

    public function deleteByActivityId($activityId);

    public function getLastestByActivityIdAndUserId($activityId, $userId);
}
