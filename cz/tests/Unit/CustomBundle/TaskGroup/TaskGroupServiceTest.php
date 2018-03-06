<?php

namespace Tests\Unit\CustomBundle\TaskGroup;

use Biz\BaseTestCase;

class TaskGroupServiceTest extends BaseTestCase
{
    public function testCreateTaskGroup()
    {
        $fields = array(
            'title' => '测试课程',
            'taskId' => 1,
        );

        $group = $this->getTaskGroupService()->createTaskGroup($fields);

        $this->assertEquals($fields['title'], $group['title']);
        $this->assertEquals($fields['taskId'], $group['taskId']);
    }

    /**
     * @expectedException \Codeages\Biz\Framework\Service\Exception\InvalidArgumentException
     */
    public function testCreateTaskGroupLackOfRequiredFileds()
    {
        $fields = array(
            'title' => '测试课程',
        );

        $group = $this->getTaskGroupService()->createTaskGroup($fields);
    }

    public function testCreateRandomTaskGroups()
    {
        $this->getTaskGroupService()->createRandomTaskGroups(2, 10);

        $groups = $this->getTaskGroupService()->findTaskGroupsByTaskId(2);

        $this->assertEquals(count($groups), 10);
    }


    public function testDeleteGroupsByTaskIds()
    {
        $this->getTaskGroupService()->deleteGroupsByTaskIds(array());

        $this->getTaskGroupService()->createRandomTaskGroups(3, 10);

        $groups = $this->getTaskGroupService()->findTaskGroupsByTaskId(3);

        $this->assertEquals(count($groups), 10);
        $this->getTaskGroupService()->deleteGroupsByTaskIds(array(3));
        $groups = $this->getTaskGroupService()->findTaskGroupsByTaskId(3);

        $this->assertEquals(count($groups), 0);
    }

    public function testCreateTaskGroupMember()
    {

    }

    protected function getTaskGroupService()
    {
        return $this->createService('CustomBundle:TaskGroup:TaskGroupService');
    }
}
