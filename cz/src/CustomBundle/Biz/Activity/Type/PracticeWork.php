<?php

namespace CustomBundle\Biz\Activity\Type;

use AppBundle\Common\ArrayToolkit;
use Biz\Activity\Config\Activity;

class PracticeWork extends Activity
{
    protected function registerListeners()
    {
        return array();
    }

    public function create($fields)
    {
        $work = ArrayToolkit::parts($fields, array(
            'fileType',
        ));
        $biz = $this->getBiz();
        $work['createdUserId'] = $biz['user']['id'];

        return $this->getPracticeWorkActivityDao()->create($work);
    }

    public function update($targetId, &$fields, $activity)
    {
        $work = ArrayToolkit::parts($fields, array(
            'fileType',
        ));

        $biz = $this->getBiz();
        $work['createdUserId'] = $biz['user']['id'];

        return $this->getPracticeWorkActivityDao()->update($targetId, $work);
    }

    public function get($targetId)
    {
        return $this->getPracticeWorkActivityDao()->get($targetId);
    }

    protected function getPracticeWorkActivityDao()
    {
        return $this->getBiz()->dao('CustomBundle:Activity:PracticeWorkActivityDao');
    }
}