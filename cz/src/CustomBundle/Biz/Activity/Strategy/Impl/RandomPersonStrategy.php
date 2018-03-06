<?php

namespace CustomBundle\Biz\Activity\Strategy\Impl;

use AppBundle\Common\ArrayToolkit;
use CustomBundle\Biz\Activity\Strategy\BaseStrategy;
use CustomBundle\Biz\Activity\Strategy\ResultShowByGroupStrategy;

class RandomPersonStrategy extends BaseStrategy implements ResultShowByGroupStrategy
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
            $results[$key] = $this->getResultService($activity['mediaType'])->findResultsByActivityIdAndUserIdsWithContents($activity['id'], $userIds);
            $results[$key]['contentCount'] = count($results[$key]);
            $results[$key]['memberCount'] = count($members);
        }
        return $results;
    }

    public function getTemplate($activity)
    {
        $template = preg_replace_callback('/([A-Z]{1})/', function ($matches) {
            return '-'.strtolower($matches[0]);
        }, $activity['mediaType']);

        return "activity/${template}/show/random-submit-person.html.twig";
    }

    protected function getGroupService()
    {
        return $this->biz->service('CustomBundle:RandomGroup:RandomGroupService');
    }
}
