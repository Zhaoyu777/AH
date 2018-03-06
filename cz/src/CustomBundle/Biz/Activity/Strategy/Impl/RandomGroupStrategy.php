<?php

namespace CustomBundle\Biz\Activity\Strategy\Impl;

use AppBundle\Common\ArrayToolkit;
use CustomBundle\Biz\Activity\Strategy\BaseStrategy;
use CustomBundle\Biz\Activity\Strategy\ResultShowByGroupStrategy;

class RandomGroupStrategy extends BaseStrategy implements ResultShowByGroupStrategy
{
    public function findGroups($task)
    {
        $members = $this->getGroupService()->findMembersByTaskId($task['id']);

        return ArrayToolkit::group($members, 'groupNum');
    }

    public function findResults($activity, $groups)
    {
        $results = array();
        foreach ($groups as $key => $members) {
            $userIds = ArrayToolkit::column($members, 'userId');
            $results[$key] = $this->getResultService($activity['mediaType'])->getLastResultByActivityIdAndUserIdsWithContents($activity['id'], $userIds);
        }

        return $results;
    }

    public function getTemplate($activity)
    {
        $template = preg_replace_callback('/([A-Z]{1})/', function ($matches) {
            return '-'.strtolower($matches[0]);
        }, $activity['mediaType']);

        return "activity/${template}/show/random-submit-group.html.twig";
    }

    protected function getGroupService()
    {
        return $this->biz->service('CustomBundle:RandomGroup:RandomGroupService');
    }
}
