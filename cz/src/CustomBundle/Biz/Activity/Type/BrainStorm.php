<?php

namespace CustomBundle\Biz\Activity\Type;

use AppBundle\Common\ArrayToolkit;
use Biz\Activity\Config\Activity;

class BrainStorm extends Activity
{
    protected function registerListeners()
    {
        return array();
    }

    public function get($targetId)
    {
        return $this->getBrainStormActivityDao()->get($targetId);
    }

    public function sync($sourceActivity, $activity)
    {
        $sourceBrainStorm = $this->getBrainStormActivityDao()->get($sourceActivity['mediaId']);
        $BrainStorm = $this->getBrainStormActivityDao()->get($activity['mediaId']);

        return $this->getBrainStormActivityDao()->update($BrainStorm['id'], $BrainStorm);
    }

    public function update($targetId, &$fields, $activity)
    {
        $BrainStorm = ArrayToolkit::parts($fields, array(
            'groupWay',
            'groupNumber',
            'submitWay',
            'score',
        ));

        $biz = $this->getBiz();
        $BrainStorm['createdUserId'] = $biz['user']['id'];

        return $this->getBrainStormActivityDao()->update($targetId, $BrainStorm);
    }

    public function delete($targetId)
    {
        return $this->getBrainStormActivityDao()->delete($targetId);
    }

    public function create($fields)
    {
        $BrainStorm = ArrayToolkit::parts($fields, array(
            'groupWay',
            'groupNumber',
            'submitWay',
        ));
        $biz = $this->getBiz();
        $BrainStorm['createdUserId'] = $biz['user']['id'];

        return $this->getBrainStormActivityDao()->create($BrainStorm);
    }

    public function find($targetIds)
    {
        return $this->getBrainStormActivityDao()->findByIds($targetIds);
    }

    protected function getBrainStormActivityDao()
    {
        return $this->getBiz()->dao('CustomBundle:Activity:BrainStormActivityDao');
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
