<?php

namespace Tests\Unit\CustomBundle\Task;

use Biz\BaseTestCase;

class TaskStatusServiceTest extends BaseTestCase
{
    public function testStartTask()
    {
        $status = $this->getTaskStatusService()->startTask(1, 1);

        $this->assertEquals($status['status'], 'start');
        $this->assertNotNull($status);
    }

    public function testEndTask()
    {
        $status = $this->getTaskStatusService()->startTask(2, 2);
        $affected = $this->getTaskStatusService()->endTask(2);

        $this->assertEquals($affected['status'], 'end');
    }

    /**
     * @expectedException \Codeages\Biz\Framework\Service\Exception\NotFoundException
     */
    public function testEndTaskWhileNotExist()
    {
        $status = $this->getTaskStatusService()->startTask(2, 2);
        $affected = $this->getTaskStatusService()->endTask($status['courseTaskId'] + 1);
    }

    public function testDeleteStatusByTaskIds()
    {
        $this->getTaskStatusService()->startTask(1, 1);
        $this->getTaskStatusService()->startTask(2, 2);

        $this->getTaskStatusService()->deleteStatusByTaskIds(array(1, 2));
        $status1 = $this->getTaskStatusService()->getStatusByTaskId(1);
        $status2 = $this->getTaskStatusService()->getStatusByTaskId(2);

        $this->assertNull($status1);
        $this->assertNull($status2);
    }

    public function testGetStatusByTaskId()
    {
        $this->getTaskStatusService()->startTask(1, 1);
        $status = $this->getTaskStatusService()->getStatusByTaskId(1);

        $this->assertEquals($status['courseTaskId'], 1);
    }

    protected function getTaskStatusService()
    {
        return $this->createService('CustomBundle:Task:TaskStatusService');
    }
}
