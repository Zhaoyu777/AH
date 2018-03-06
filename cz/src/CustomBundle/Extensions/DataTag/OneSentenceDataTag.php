<?php

namespace CustomBundle\Extensions\DataTag;

use AppBundle\Common\ArrayToolkit;
use AppBundle\Extensions\DataTag\DataTag;
use AppBundle\Extensions\DataTag\BaseDataTag;

class OneSentenceDataTag extends BaseDataTag implements DataTag
{
    public function getData(array $arguments)
    {
        if (empty($arguments['taskId'])) {
            throw new \InvalidArgumentException($this->getServiceKernel()->trans('taskId参数缺失'));
        }
        if (empty($arguments['userId'])) {
            throw new \InvalidArgumentException($this->getServiceKernel()->trans('userId参数缺失'));
        }

        $result = $this->getResultService()->getResultByTaskIdAndUserId($arguments['taskId'], $arguments['userId']);
        if (empty($result)) {
            return false;
        }

        return $result;
    }

    protected function getResultService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:Activity:OneSentenceResultService');
    }

    protected function getUserService()
    {
        return $this->getServiceKernel()->createService('User:UserService');
    }
}
