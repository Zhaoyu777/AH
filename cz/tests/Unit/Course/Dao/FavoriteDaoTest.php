<?php

namespace Tests\Unit\Course\Dao;

use Tests\Unit\Base\BaseDaoTestCase;

class FavoriteDaoTest extends BaseDaoTestCase
{
    public function testGetByUserIdAndCourseId()
    {
        $expected = $this->mockDataObject();

        $res = $this->getDao()->getByUserIdAndCourseId(1, 1);

        $this->assertEquals($expected, $res);
    }

    // 覆盖searchByUserId
    public function testSearch()
    {
        $expected = array();
        $expected[] = $this->mockDataObject();
        $expected[] = $this->mockDataObject(array('userId' => 2));
        $expected[] = $this->mockDataObject(array('courseId' => 2, 'courseSetId' => 2));

        $testConditions = array(
            array(
                'condition' => array('courseId' => 1),
                'expectedResults' => array($expected[0], $expected[1]),
                'expectedCount' => 2,
            ),
            array(
                'condition' => array('userId' => 1),
                'expectedResults' => array($expected[0], $expected[2]),
                'expectedCount' => 2,
            ),
            array(
                'condition' => array('type' => 'course'),
                'expectedResults' => $expected,
                'expectedCount' => 3,
            ),
            array(
                'condition' => array('courseSetId' => 1),
                'expectedResults' => array($expected[0], $expected[1]),
                'expectedCount' => 2,
            ),
            array(
                'condition' => array('courseSetIds' => array(1, 2)),
                'expectedResults' => $expected,
                'expectedCount' => 3,
            ),
            array(
                'condition' => array('excludeCourseIds' => array(1)),
                'expectedResults' => array($expected[2]),
                'expectedCount' => 1,
            ),
        );
    }

    public function testGetByUserIdAndCourseSetId()
    {
        $expected = $this->mockDataObject();

        $res = $this->getDao()->getByUserIdAndCourseSetId(1, 1);

        $this->assertEquals($expected, $res);
    }

    public function testCountByUserId()
    {
        $expected = array();
        $expected[] = $this->mockDataObject();
        $expected[] = $this->mockDataObject(array('userId' => 2));
        $expected[] = $this->mockDataObject(array('courseId' => 2));

        $res = $this->getDao()->countByUserId(1);

        $this->assertEquals(2, $res);
    }

    protected function getDefaultMockFields()
    {
        return array(
            'courseId' => 1,
            'userId' => 1,
            'type' => 'course',
            'courseSetId' => 1,
        );
    }
}
