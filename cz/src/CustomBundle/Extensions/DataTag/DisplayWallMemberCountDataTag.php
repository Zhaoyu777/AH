<?php

namespace CustomBundle\Extensions\DataTag;

use AppBundle\Extensions\DataTag\BaseDataTag;
use AppBundle\Extensions\DataTag\DataTag;

class DisplayWallMemberCountDataTag extends BaseDataTag implements DataTag
{
    public function getData(array $arguments)
    {
        if (empty($arguments['taskId'])) {
            throw new \InvalidArgumentException('taskId参数缺失');
        }

        return $this->getResultService()->countResultByTaskId($arguments['taskId']);
    }

    protected function getResultService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:DisplayWall:ResultService');
    }
}
