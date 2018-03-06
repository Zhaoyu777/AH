<?php

namespace CustomBundle\Extensions\DataTag;

use AppBundle\Extensions\DataTag\DataTag;
use AppBundle\Extensions\DataTag\BaseDataTag;

class RealAttendMemberCountDataTag extends BaseDataTag implements DataTag
{
    public function getData(array $arguments)
    {
        $user = $this->getCurrentUser();
        if (empty($arguments['lessonId'])) {
            throw new \InvalidArgumentException($this->getServiceKernel()->trans('lessonId参数缺失'));
        }

        return $this->getSignInService()->getRealAttendMemberCountByLessonId($arguments['lessonId']);
    }

    protected function getSignInService()
    {
        return $this->getServiceKernel()->createService('CustomBundle:SignIn:SignInService');
    }
}
