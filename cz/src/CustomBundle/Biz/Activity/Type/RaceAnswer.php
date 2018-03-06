<?php

namespace CustomBundle\Biz\Activity\Type;

use AppBundle\Common\ArrayToolkit;
use Biz\Activity\Config\Activity;

class RaceAnswer extends Activity
{
    protected function registerListeners()
    {
        return array();
    }

    public function get($targetId)
    {
        return $this->getRaceAnswerActivityDao()->get($targetId);
    }

    public function sync($sourceActivity, $activity)
    {
        $sourceRaceAnswer = $this->getRaceAnswerActivityDao()->get($sourceActivity['mediaId']);
        $raceAnswer = $this->getRaceAnswerActivityDao()->get($activity['mediaId']);
        $raceAnswer['duration'] = $sourceRaceAnswer['duration'];

        return $this->getRaceAnswerActivityDao()->update($raceAnswer['id'], $raceAnswer);
    }

    public function update($targetId, &$fields, $activity)
    {
        $biz = $this->getBiz();
        $raceAnswer['createdUserId'] = $biz['user']['id'];

        return $this->getRaceAnswerActivityDao()->update($targetId, $raceAnswer);
    }

    public function delete($targetId)
    {
        return $this->getRaceAnswerActivityDao()->delete($targetId);
    }

    public function create($fields)
    {
        $biz = $this->getBiz();
        $raceAnswer['createdUserId'] = $biz['user']['id'];

        return $this->getRaceAnswerActivityDao()->create($raceAnswer);
    }

    public function find($targetIds)
    {
        return $this->getRaceAnswerActivityDao()->findByIds($targetIds);
    }

    protected function getRaceAnswerActivityDao()
    {
        return $this->getBiz()->dao('CustomBundle:Activity:RaceAnswerActivityDao');
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
