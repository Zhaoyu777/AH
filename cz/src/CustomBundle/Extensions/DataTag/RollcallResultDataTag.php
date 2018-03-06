<?php

namespace CustomBundle\Extensions\DataTag;

use AppBundle\Extensions\DataTag\DataTag;
use AppBundle\Extensions\DataTag\BaseDataTag;

class RollcallResultDataTag extends BaseDataTag implements DataTag
{
    public function getData(array $arguments)
    {
        $user = $this->getCurrentUser();
        if (empty($arguments['taskId'])) {
            throw new \InvalidArgumentException($this->getServiceKernel()->trans('taskId参数缺失'));
        }

        return $this->getRollcallResultService()->getResultByTaskIdAndUserId($arguments['taskId'], $user['id']);
    }

    protected function getRollcallResultService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:Activity:RollcallResultService');
    }
}
