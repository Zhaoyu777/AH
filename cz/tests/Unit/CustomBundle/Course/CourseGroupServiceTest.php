<?php

namespace Tests\Unit\CustomBundle\Course;

use Biz\BaseTestCase;

class CourseGroupServiceTest extends BaseTestCase
{
    public function testCreateCourseGroup()
    {
        $group = $this->mockCourseGroup();

        $created = $this->getCourseGroupService()->createCourseGroup($group);
        $this->assertEquals($created['title'], $group['title']);
    }

    /**
     * @expectedException \Codeages\Biz\Framework\Service\Exception\InvalidArgumentException
     */
    public function testCreateCourseGroupWhileLackRequiredFields()
    {
        $group = array('title' => 'course group');

        $created = $this->getCourseGroupService()->createCourseGroup($group);
    }

    public function testDeleteGroup()
    {
        $group = $this->mockCourseGroup();

        $created = $this->getCourseGroupService()->createCourseGroup($group);

        $this->getCourseGroupService()->deleteGroup($created['id']);

        $group = $this->getCourseGroupService()->getCourseGroup($created['id']);
        $this->assertNull($group);
    }

    /**
     * @expectedException \Codeages\Biz\Framework\Service\Exception\NotFoundException
     */
    public function testDeleteGroupWhileNotExist()
    {
        $group = $this->mockCourseGroup();

        $created = $this->getCourseGroupService()->createCourseGroup($group);

        $this->getCourseGroupService()->deleteGroup($created['id']+1);
    }

    public function testGetCourseGroup()
    {
        $group = $this->mockCourseGroup();

        $created = $this->getCourseGroupService()->createCourseGroup($group);

        $group = $this->getCourseGroupService()->getCourseGroup($created['id']);

        $this->assertEquals($group, $created);
    }

    protected function mockCourseGroup($courseId = 1)
    {
        return array(
            'title' => 'course group',
            'courseId' => $courseId,
        );
    }

    protected function getCourseGroupService()
    {
        return $this->createService('CustomBundle:Course:CourseGroupService');
    }
}
