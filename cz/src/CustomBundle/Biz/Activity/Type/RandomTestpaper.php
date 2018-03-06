<?php

namespace CustomBundle\Biz\Activity\Type;

use AppBundle\Common\ArrayToolkit;
use Biz\Activity\Config\Activity;

class RandomTestpaper extends Activity
{
    protected function registerListeners()
    {
        return array();
    }

    public function get($targetId)
    {
        return $this->getRandomTestpaperActivityDao()->get($targetId);
    }

    public function sync($sourceActivity, $activity)
    {
        // $sourceBrainStorm = $this->getRandomTestpaperActivityDao()->get($sourceActivity['mediaId']);
        // $BrainStorm = $this->getRandomTestpaperActivityDao()->get($activity['mediaId']);

        // return $this->getRandomTestpaperActivityDao()->update($BrainStorm['id'], $BrainStorm);
    }

    public function update($targetId, &$fields, $activity)
    {
        $metas = ArrayToolkit::parts($fields, array(
            'range',
            'difficulty',
            'scores',
            'counts',
            'missScores'
        ));
        if (empty($fields['content'])) {
            $fields['content'] = '';
        }
        $randomTestpaper = array(
            'name' => $fields['title'],
            'description' => $fields['content'],
            'passedScore' => $fields['passedScore'],
            'metas' => $metas,
            'itemCount' => $fields['itemCount'],
            'totalScore' => $fields['totalScore'],
        );

        return $this->getRandomTestpaperActivityDao()->update($targetId, $randomTestpaper);
    }

    public function delete($targetId)
    {
        return $this->getRandomTestpaperActivityDao()->delete($targetId);
    }

    public function create($fields)
    {
        $metas = ArrayToolkit::parts($fields, array(
            'range',
            'difficulty',
            'scores',
            'counts',
            'missScores'
        ));
        if (empty($fields['content'])) {
            $fields['content'] = '';
        }
        $randomTestpaper = array(
            'name' => $fields['title'],
            'description' => $fields['content'],
            'passedScore' => $fields['passedScore'],
            'createdUserId' => $fields['fromUserId'],
            'metas' => $metas,
            'itemCount' => $fields['itemCount'],
            'totalScore' => $fields['totalScore'],
        );

        return $this->getRandomTestpaperActivityDao()->create($randomTestpaper);
    }

    public function find($targetIds)
    {
        return $this->getRandomTestpaperActivityDao()->findByIds($targetIds);
    }

    protected function getRandomTestpaperActivityDao()
    {
        return $this->getBiz()->dao('CustomBundle:Activity:RandomTestpaperActivityDao');
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
