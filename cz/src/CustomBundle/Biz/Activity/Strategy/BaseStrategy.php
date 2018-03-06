<?php

namespace CustomBundle\Biz\Activity\Strategy;

use Codeages\Biz\Framework\Context\Biz;
use AppBundle\Common\ArrayToolkit;

class BaseStrategy
{
    protected $biz;
    protected $container;

    public function __construct($biz, $container)
    {
        $this->biz = $biz;
        $this->container = $container;
    }

    protected function userAvatar($avatar)
    {
        return empty($avatar) ? null : $this->getWebExtension()->getFilePath($avatar, 'avatar.png');
    }

    protected function getAppExtension()
    {
        return $this->container->get('web.twig.app_extension');
    }

    protected function getWebExtension()
    {
        return $this->container->get('web.twig.extension');
    }

    protected function getResultService($activityType)
    {
        $aliase = ucfirst($activityType);

        return $this->biz->service("Custom:${aliase}:ResultService");
    }

    protected function getSettingService()
    {
        return $this->biz->service('System:SettingService');
    }

    protected function getCourseMemberService()
    {
        return $this->biz->service('CustomBundle:Course:MemberService');
    }

    protected function getStatusService()
    {
        return $this->biz->service('CustomBundle:Task:TaskStatusService');
    }
}
