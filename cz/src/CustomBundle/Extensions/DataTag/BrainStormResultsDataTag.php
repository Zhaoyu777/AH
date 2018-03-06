<?php

namespace CustomBundle\Extensions\DataTag;

use AppBundle\Common\ArrayToolkit;
use AppBundle\Extensions\DataTag\DataTag;
use AppBundle\Extensions\DataTag\BaseDataTag;

class BrainStormResultsDataTag extends BaseDataTag implements DataTag
{
    public function getData(array $arguments)
    {
        if (empty($arguments['taskId'])) {
            throw new \InvalidArgumentException($this->getServiceKernel()->trans('taskId参数缺失'));
        }
        $results = $this->getResultService()->findResultsByTaskId($arguments['taskId'], 5);
        $userIds = ArrayToolkit::column($results, 'userId');
        $users = $this->getUserService()->findUsersByIds($userIds);

        foreach ($results as $key => &$result) {
            $result['truename'] = $users[$result['userId']]['truename'];
            $result['number'] = $users[$result['userId']]['number'];
        }

        return $results;
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
