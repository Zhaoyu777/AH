<?php

namespace CustomBundle\Biz\Activity\Service\Impl;

use AppBundle\Common\ArrayToolkit;
use Codeages\Biz\Framework\Event\Event;
use Biz\Activity\Service\Impl\ActivityLearnLogServiceImpl as BaseActivityLearnLogServiceImpl;

class ActivityLearnLogServiceImpl extends BaseActivityLearnLogServiceImpl
{
    public function deleteLearnLogsByTaskId($taskIds)
    {
        if (empty($taskIds)) {
            return ;
        }

        return $this->getActivityLearnLogDao()->deleteByTaskIds($taskIds);
    }

    protected function getActivityLearnLogDao()
    {
        return $this->createDao('CustomBundle:Activity:ActivityLearnLogDao');
    }
}
