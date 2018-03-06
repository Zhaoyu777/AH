<?php

namespace CustomBundle\Twig;

use Codeages\Biz\Framework\Context\Biz;
use Topxia\Service\Common\ServiceKernel;
use AppBundle\Common\ArrayToolkit;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ActivityExtension extends \Twig_Extension
{
    /**
     * @var Biz
     */
    protected $biz;

    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container, Biz $biz)
    {
        $this->container = $container;
        $this->biz = $biz;
    }

    public function getFilters()
    {
        return array(
        );
    }

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('custom_activity_metas', array($this, 'getActivityMeta')),
        );
    }

    public function getActivityMeta($type = 'in')
    {
        $allActivities = $this->container->get('extension.manager')->getActivities();
        foreach ($allActivities as &$activity) {
            $activity['meta']['name'] = $this->container->get('translator')->trans($activity['meta']['name']);
        }
        $stageActivityType = $this->container->getParameter('stageActivityType');
        $activities = array();
        foreach ($stageActivityType[$type] as $sort => $taskTypes) {
            foreach ($taskTypes as $taskType) {
                if (!empty($allActivities[$taskType])) {
                    $activities[$sort][$taskType] = $allActivities[$taskType];
                }
            }

            $activities[$sort] = array_map(function ($activity) {
                return $activity['meta'];
            }, $activities[$sort]);
        }


        return $activities;
    }

    protected function getTaskService()
    {
        return $this->biz->service('CustomBundle:Task:TaskService');
    }

    public function getName()
    {
        return 'custom_activity_twig';
    }
}
