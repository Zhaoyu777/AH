<?php

namespace CustomBundle\Extensions\DataTag;

use AppBundle\Extensions\DataTag\BaseDataTag;
use AppBundle\Extensions\DataTag\DataTag;

class TaskResultDataTag extends BaseDataTag implements DataTag
{
    public function getData(array $arguments)
    {
        if (empty($arguments['taskId'])) {
            throw new \InvalidArgumentException('taskId参数缺失');
        }

        return $this->getTaskResultService()->getUserTaskResultByTaskId($arguments['taskId']);
    }

    protected function getTaskResultService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:Task:TaskResultService');
    }
}
