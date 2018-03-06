<?php

namespace CustomBundle\Extensions\DataTag;

use AppBundle\Extensions\DataTag\DataTag;
use AppBundle\Extensions\DataTag\BaseDataTag;

class DisplayWallAnswerDataTag extends BaseDataTag implements DataTag
{
    public function getData(array $arguments)
    {
        $user = $this->getCurrentUser();
        if (empty($arguments['taskId'])) {
            throw new \InvalidArgumentException($this->getServiceKernel()->trans('taskId参数缺失'));
        }

        return $this->getDisplayWallResultService()->getResultByUserIdAndTaskId($user['id'], $arguments['taskId'], true);
    }

    protected function getDisplayWallResultService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:DisplayWall:ResultService');
    }
}
