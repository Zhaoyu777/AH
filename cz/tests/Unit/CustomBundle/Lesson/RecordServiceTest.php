<?php

namespace Tests\Unit\CustomBundle\Lesson;

use Biz\BaseTestCase;

class RecordServiceRest extends BaseTestCase
{
    /**
     * @expectedException \Codeages\Biz\Framework\Service\Exception\InvalidArgumentException
     */
    public function testCreateWithoutRequiredFields()
    {
        $record = array(
            'courseSetId' => 1,
            'courseId' => 1,
            'lessonId' => 1,
        );

        $this->getRecordService()->create($record);
    }

    public function testCreate()
    {
        $courseSet = array(
            'title' => '课程',
            'type' => 'instant',
        );
        $courseSet = $this->getCourseSetService()->createInstantCourseSet($courseSet);

        $courseFields = array(
            'title' => '第一个教学计划',
            'courseSetId' => $courseSet['id'],
            'learnMode' => 'lockMode',
            'expiryDays' => 0,
            'expiryMode' => 'forever',
        );

        $course = $this->getCourseService()->createCourse($courseFields);

        $user = $this->getCurrentUser();

        $record = array(
            'courseSetId' => $courseSet['id'],
            'courseId' => $course['id'],
            'lessonId' => 1,
            'teacherId' => $user['id'],
            'taskId' => 1
        );

        $result = $this->getRecordService()->create($record);

        $this->assertEquals(1, $result['courseSetId']);
    }

    /**
     * @expectedException \Codeages\Biz\Framework\Service\Exception\NotFoundException
     */
    public function testUpdateRecordNotExsit()
    {
        $record = $this->mockRecord();

        $this->getRecordService()->update($record['id'] + 1, array(
            'teacherId' => '2'
        ));
    }

    public function testUpdate()
    {
        $record = $this->mockRecord();

        $result = $this->getRecordService()->update($record['id'], array(
            'teacherId' => '2'
        ));

        $this->assertEquals(2, $result['teacherId']);
    }

    public function testDelete()
    {
        $record = $this->mockRecord();

        $this->getRecordService()->delete($record['id']);

        $record = $this->getRecordService()->getByRecordId($record['id']);

        $this->assertNull($record);
    }

    public function testGetByRecordId()
    {
        $record = $this->mockRecord();

        $result = $this->getRecordService()->getByRecordId($record['id']);

        $this->assertEquals(1, $result['courseSetId']);
    }

    public function testGetByRecordIdNotExsit()
    {
        $record = $this->mockRecord();

        $result = $this->getRecordService()->getByRecordId($record['id'] + 1);

        $this->assertEquals(0, count($result));
    }

    public function testGetByLessonId()
    {
        $record = $this->mockRecord();

        $result = $this->getRecordService()->getByLessonId($record['lessonId']);

        $this->assertEquals(1, $result['courseSetId']);
    }

    public function testGetByLessonIdNotExsit()
    {
        $record = $this->mockRecord();

        $result = $this->getRecordService()->getByLessonId($record['lessonId'] + 1);

        $this->assertEquals(0, count($result));
    }

    protected function mockRecord()
    {
        $courseSet = array(
            'title' => '课程',
            'type' => 'instant',
        );
        $courseSet = $this->getCourseSetService()->createInstantCourseSet($courseSet);

        $courseFields = array(
            'title' => '第一个教学计划',
            'courseSetId' => $courseSet['id'],
            'learnMode' => 'lockMode',
            'expiryDays' => 0,
            'expiryMode' => 'forever',
        );

        $course = $this->getCourseService()->createCourse($courseFields);

        $record = array(
            'courseSetId' => $courseSet['id'],
            'courseId' => $course['id'],
            'lessonId' => 1,
            'teacherId' => 1,
            'taskId' => 1
        );

        return $this->getRecordService()->create($record);
    }

    protected function getCourseSetService()
    {
        return $this->createService('CustomBundle:Course:CourseSetService');
    }

    protected function getCourseService()
    {
        return $this->createService('CustomBundle:Course:CourseService');
    }

    protected function getRecordService()
    {
        return $this->createService('CustomBundle:Lesson:RecordService');
    }
}
