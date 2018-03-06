<?php

namespace CustomBundle\Extensions\DataTag;

use AppBundle\Common\ArrayToolkit;
use AppBundle\Extensions\DataTag\DataTag;
use AppBundle\Extensions\DataTag\BaseDataTag;

class RaceAnswerResultsDataTag extends BaseDataTag implements DataTag
{
    public function getData(array $arguments)
    {
        if (empty($arguments['taskId'])) {
            throw new \InvalidArgumentException($this->getServiceKernel()->trans('taskId参数缺失'));
        }
        $results = $this->getRaceAnswerService()->findResultByTaskId($arguments['taskId'], 5);
        $userIds = ArrayToolkit::column($results, 'userId');
        $users = $this->getUserService()->findUsersByIds($userIds);

        foreach ($results as $key => &$result) {
            $result['truename'] = $users[$result['userId']]['truename'];
            $result['number'] = $users[$result['userId']]['number'];
            $result['rank'] = $this->getRaceAnswerService()->getRankByTaskIdAndCreatedTime($result['courseTaskId'], $result['createdTime']);
        }

        return ArrayToolkit::index($results, 'userId');
    }

    protected function getRaceAnswerService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:RaceAnswer:RaceAnswerService');
    }

    protected function getUserService()
    {
        return $this->getServiceKernel()->createService('User:UserService');
    }
}
