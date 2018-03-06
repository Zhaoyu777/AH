<?php

namespace CustomBundle\Extensions\DataTag;

use AppBundle\Common\ArrayToolkit;
use AppBundle\Extensions\DataTag\DataTag;
use AppBundle\Extensions\DataTag\BaseDataTag;

class QuestionnaireResultDataTag extends BaseDataTag implements DataTag
{
    public function getData(array $arguments)
    {
        if (empty($arguments['taskId'])) {
            throw new \InvalidArgumentException($this->getServiceKernel()->trans('taskId参数缺失'));
        }

        if (empty($arguments['userId'])) {
            throw new \InvalidArgumentException($this->getServiceKernel()->trans('userId参数缺失'));
        }

        $result = $this->getQuestionnaireService()->getQuestionnaireResultByTaskIdAndUserId($arguments['taskId'], $arguments['userId']);

        return $result;
    }

    protected function getQuestionnaireService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:Questionnaire:QuestionnaireService');
    }
}
