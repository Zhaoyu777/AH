<?php

namespace CustomBundle\Extensions\DataTag;

use AppBundle\Extensions\DataTag\DataTag;
use AppBundle\Extensions\DataTag\BaseDataTag;

class TaskStatusDataTag extends BaseDataTag implements DataTag
{
    public function getData(array $arguments)
    {
        $status = $this->getStatusService()->getStatusByTaskId($arguments['taskId']);
        if (empty($status)) {
            return null;
        }
        if (isset($arguments['type']) && $arguments['type'] == 'complect') {
            return $status;
        }
        
        return $status['status'];
    }

    protected function getStatusService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:Task:TaskStatusService');
    }
}
