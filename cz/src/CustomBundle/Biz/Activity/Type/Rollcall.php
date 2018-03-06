<?php

namespace CustomBundle\Biz\Activity\Type;

use AppBundle\Common\ArrayToolkit;
use Biz\Activity\Config\Activity;

class Rollcall extends Activity
{
    protected function registerListeners()
    {
        return array();
    }

    public function get($targetId)
    {
        return $this->getRollcallActivityDao()->get($targetId);
    }

    public function sync($sourceActivity, $activity)
    {
        $sourceRollcall = $this->getRollcallActivityDao()->get($sourceActivity['mediaId']);
        $rollcall = $this->getRollcallActivityDao()->get($activity['mediaId']);
        $rollcall['duration'] = $sourceRollcall['duration'];

        return $this->getRollcallActivityDao()->update($rollcall['id'], $rollcall);
    }

    public function update($targetId, &$fields, $activity)
    {
        $biz = $this->getBiz();
        $rollcall['createdUserId'] = $biz['user']['id'];

        return $this->getRollcallActivityDao()->update($targetId, $rollcall);
    }

    public function delete($targetId)
    {
        return $this->getRollcallActivityDao()->delete($targetId);
    }

    public function create($fields)
    {
        $biz = $this->getBiz();
        $rollcall['createdUserId'] = $biz['user']['id'];

        return $this->getRollcallActivityDao()->create($rollcall);
    }

    public function find($targetIds)
    {
        return $this->getRollcallActivityDao()->findByIds($targetIds);
    }

    protected function getRollcallActivityDao()
    {
        return $this->getBiz()->dao('CustomBundle:Activity:RollcallActivityDao');
    }

    protected function getActivityLearnLogService()
    {
        return $this->getBiz()->service('Activity:ActivityLearnLogService');
    }

    protected function getActivityService()
    {
        return $this->getBiz()->service('Activity:ActivityService');
    }
}
