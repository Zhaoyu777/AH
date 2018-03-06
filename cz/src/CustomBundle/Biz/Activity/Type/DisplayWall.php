<?php

namespace CustomBundle\Biz\Activity\Type;

use AppBundle\Common\ArrayToolkit;
use Biz\Activity\Config\Activity;

class DisplayWall extends Activity
{
    protected function registerListeners()
    {
        return array();
    }

    public function get($targetId)
    {
        return $this->getDisplayWallActivityDao()->get($targetId);
    }

    public function sync($sourceActivity, $activity)
    {
        $sourceDisplayWall = $this->getDisplayWallActivityDao()->get($sourceActivity['mediaId']);
        $displayWall = $this->getDisplayWallActivityDao()->get($activity['mediaId']);

        return $this->getDisplayWallActivityDao()->update($displayWall['id'], $displayWall);
    }

    public function update($targetId, &$fields, $activity)
    {
        $displayWall = ArrayToolkit::parts($fields, array(
            'groupWay',
            'groupNumber',
            'submitWay',
            'score',
        ));

        $biz = $this->getBiz();
        $displayWall['createdUserId'] = $biz['user']['id'];
        $this->getCourseDraftService()->deleteCourseDrafts($activity['fromCourseId'], $activity['id'], $biz['user']['id']);

        return $this->getDisplayWallActivityDao()->update($targetId, $displayWall);
    }

    public function delete($targetId)
    {
        return $this->getDisplayWallActivityDao()->delete($targetId);
    }

    public function create($fields)
    {
        $displayWall = ArrayToolkit::parts($fields, array(
            'groupWay',
            'groupNumber',
            'submitWay',
            'score',
        ));
        $biz = $this->getBiz();
        $displayWall['createdUserId'] = $biz['user']['id'];
        if ($displayWall['groupWay'] == 'none') {
            $displayWall['submitWay'] = 'person';
        }

        return $this->getDisplayWallActivityDao()->create($displayWall);
    }

    public function find($targetIds)
    {
        return $this->getDisplayWallActivityDao()->findByIds($targetIds);
    }

    protected function getDisplayWallActivityDao()
    {
        return $this->getBiz()->dao('CustomBundle:Activity:DisplayWallActivityDao');
    }

    protected function getActivityLearnLogService()
    {
        return $this->getBiz()->service('Activity:ActivityLearnLogService');
    }

    protected function getActivityService()
    {
        return $this->getBiz()->service('Activity:ActivityService');
    }

    protected function getCourseDraftService()
    {
        return $this->getBiz()->service('Course:CourseDraftService');
    }
}
