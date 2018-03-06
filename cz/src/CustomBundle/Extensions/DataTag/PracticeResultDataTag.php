<?php

namespace CustomBundle\Extensions\DataTag;

use AppBundle\Extensions\DataTag\BaseDataTag;
use AppBundle\Extensions\DataTag\DataTag;

class PracticeResultDataTag extends BaseDataTag implements DataTag
{
    public function getData(array $arguments)
    {
        if (empty($arguments['activityId'])) {
            throw new \InvalidArgumentException('activityId参数缺失');
        }

        return $this->getResultService()->findResultsByActivityIdWithContents($arguments['activityId'], 5);
    }

    protected function getResultService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:Practice:PracticeResultService');
    }
}
