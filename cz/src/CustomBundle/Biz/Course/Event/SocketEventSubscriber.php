<?php

namespace CustomBundle\Biz\Course\Event;

use CustomBundle\Common\PushMsgToolkit;
use AppBundle\Common\ArrayToolkit;
use Codeages\Biz\Framework\Event\Event;
use Codeages\Biz\Framework\Context\Biz;
use Codeages\PluginBundle\Event\EventSubscriber;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SocketEventSubscriber extends EventSubscriber
{
    private $container;

    public function __construct(Biz $biz, $container)
    {
        parent::__construct($biz);
        $this->container = $container;
    }

    protected function getFilePath($uri, $default = '')
    {
        if (empty($uri) &&  empty($default)) {
            return null;
        }

        return $this->getWebExtension()->getFpath($uri, $default);
    }

    protected function generateUrl($route, $parameters = array(), $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        return $this->container->get('router')->generate($route, $parameters, $referenceType);
    }

    protected function getWebExtension()
    {
        return $this->container->get('web.twig.extension');
    }

    protected function emit($message, $room, $data)
    {
        return $this->getPushTool()->sendMsg($message, $room, $data);
    }

    protected function getPushTool()
    {
        return PushMsgToolkit::getInstance();
    }

    protected function createService($alias)
    {
        return $this->getBiz()->service($alias);
    }
}
