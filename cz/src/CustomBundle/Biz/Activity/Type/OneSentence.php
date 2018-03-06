<?php

namespace CustomBundle\Biz\Activity\Type;

use AppBundle\Common\ArrayToolkit;
use Biz\Activity\Config\Activity;

class OneSentence extends Activity
{
    protected function registerListeners()
    {
        return array();
    }

    public function get($targetId)
    {
        return $this->getOneSentenceActivityDao()->get($targetId);
    }

    public function sync($sourceActivity, $activity)
    {
        $sourceOneSentence = $this->getOneSentenceActivityDao()->get($sourceActivity['mediaId']);
        $oneSentence = $this->getOneSentenceActivityDao()->get($activity['mediaId']);
        $oneSentence['duration'] = $sourceOneSentence['duration'];

        return $this->getOneSentenceActivityDao()->update($oneSentence['id'], $oneSentence);
    }

    public function update($targetId, &$fields, $activity)
    {
        $oneSentence = ArrayToolkit::parts($fields, array(
            'score',
        ));

        $biz = $this->getBiz();
        $oneSentence['createdUserId'] = $biz['user']['id'];

        return $this->getOneSentenceActivityDao()->update($targetId, $oneSentence);
    }

    public function delete($targetId)
    {
        return $this->getOneSentenceActivityDao()->delete($targetId);
    }

    public function create($fields)
    {
        $oneSentence = ArrayToolkit::parts($fields, array(
            'score',
        ));
        $biz = $this->getBiz();
        $oneSentence['createdUserId'] = $biz['user']['id'];

        return $this->getOneSentenceActivityDao()->create($oneSentence);
    }

    public function find($targetIds)
    {
        return $this->getOneSentenceActivityDao()->findByIds($targetIds);
    }

    protected function getOneSentenceActivityDao()
    {
        return $this->getBiz()->dao('CustomBundle:Activity:OneSentenceActivityDao');
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
