<?php

namespace Tests\Unit\CustomBundle\Course;

use Biz\BaseTestCase;

class GroupMemberServiceTest extends BaseTestCase
{
    public function testCreateGroupMember()
    {
        $group = $this->mockGroupMember();

        $created = $this->getGroupMemberService()->createGroupMember($group);
        $this->assertEquals($created['groupId'], $group['groupId']);
    }

    /**
     * @expectedException \Codeages\Biz\Framework\Service\Exception\InvalidArgumentException
     */
    public function testCreateGroupMemberWhileLackRequiredFields()
    {
        $group = array('groupId' => 1);

        $created = $this->getGroupMemberService()->createGroupMember($group);
    }

    public function testDeleteGroup()
    {
        $group = $this->mockGroupMember();

        $created = $this->getGroupMemberService()->createGroupMember($group);

        $this->getGroupMemberService()->deleteGroupMember($created['id']);

        $group = $this->getGroupMemberService()->getGroupMember($created['id']);
        $this->assertNull($group);
    }

    /**
     * @expectedException \Codeages\Biz\Framework\Service\Exception\NotFoundException
     */
    public function testDeleteGroupWhileNotExist()
    {
        $group = $this->mockGroupMember();

        $created = $this->getGroupMemberService()->createGroupMember($group);

        $this->getGroupMemberService()->deleteGroupMember($created['id']+1);
    }

    public function testGetGroupMember()
    {
        $group = $this->mockGroupMember();

        $created = $this->getGroupMemberService()->createGroupMember($group);

        $group = $this->getGroupMemberService()->getGroupMember($created['id']);

        $this->assertEquals($group, $created);
    }

    protected function mockGroupMember($groupId = 1)
    {
        return array(
            'groupId' => $groupId,
            'courseMemberId' => 1,
        );
    }

    protected function getGroupMemberService()
    {
        return $this->createService('CustomBundle:Course:GroupMemberService');
    }
}
