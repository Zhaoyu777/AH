<?php

namespace CustomBundle\Extensions\DataTag;

use AppBundle\Common\ArrayToolkit;
use AppBundle\Extensions\DataTag\DataTag;
use AppBundle\Extensions\DataTag\BaseDataTag;

class UserBrainStormDataTag extends BaseDataTag implements DataTag
{
    public function getData(array $arguments)
    {
        if (empty($arguments['taskId']) || empty($arguments['userId'])) {
            throw new \InvalidArgumentException($this->getServiceKernel()->trans('参数缺失'));
        }
        $result = $this->getResultService()->getResultByTaskIdAndUserId($arguments['taskId'], $arguments['userId']);

        return $result;
    }

    protected function getResultService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:Activity:BrainStormResultService');
    }

    protected function getUserService()
    {
        return $this->getServiceKernel()->createService('User:UserService');
    }
}
