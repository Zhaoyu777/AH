<?php

namespace AppBundle\Component\Export;

use Symfony\Component\DependencyInjection\ContainerInterface;

class Factory
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param $name
     * @param $conditions
     *
     * @return Exporter
     */
    public function create($name, $conditions)
    {
        $export = $this->exportMap($name);

        return new $export($this->container, $conditions);
    }

    private function exportMap($name)
    {
        $map = array(
            'invite-records' => 'AppBundle\Component\Export\Invite\InviteRecordsExporter',
            'user-invite-records' => 'AppBundle\Component\Export\Invite\InviteUserRecordsExporter',
            'order' => 'AppBundle\Component\Export\Order\OrderExporter',
            'course-overview-student-list' => 'AppBundle\Component\Export\Course\OverviewStudentExporter',
            'course-overview-task-list' => 'AppBundle\Component\Export\Course\OverviewTaskExporter',
            'course-overview-normal-task-detail' => 'AppBundle\Component\Export\Course\OverviewNormalTaskDetailExporter',
            'course-overview-testpaper-task-detail' => 'AppBundle\Component\Export\Course\OverviewTestpaperTaskDetailExporter',
            'bill-cash-flow' => 'AppBundle\Component\Export\Bill\CashBillExporter',
            'bill-coin-flow' => 'AppBundle\Component\Export\Bill\CoinBillExporter',
        );

        return $map[$name];
    }
}
