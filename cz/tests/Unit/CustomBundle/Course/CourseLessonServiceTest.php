<?php

namespace Tests\Unit\CustomBundle\Course;

use Biz\BaseTestCase;

class CourseLessonServiceTest extends BaseTestCase
{
    public function testCreateCourseLesson()
    {
        $lesson = $this->mockCourseLesson();
        $created = $this->getCourseLessonService()->createCourseLesson($lesson);

        $this->assertEquals($created['title'], $lesson['title']);
    }

    /**
     * @expectedException \Codeages\Biz\Framework\Service\Exception\InvalidArgumentException
     */
    public function testCreateCourseLessonLackRequired()
    {
        $lesson = array(
            'number' => 1,
            'seq' => 1,
            'title' => '课次的title',
        );
        $this->getCourseLessonService()->createCourseLesson($lesson);
    }

    public function testUpdateCourseLesson()
    {
        $lesson = $this->mockCourseLesson();
        $created = $this->getCourseLessonService()->createCourseLesson($lesson);

        $fields = array(
            'teachAim' => '教学目的',
        );
        $updated = $this->getCourseLessonService()->updateCourseLesson($created['id'], $fields);

        $this->assertEquals($updated['teachAim'], $fields['teachAim']);
    }

    /**
     * @expectedException \Codeages\Biz\Framework\Service\Exception\NotFoundException
     */
    public function testUpdateCourseLessonWhileNotExist()
    {
        $lesson = $this->mockCourseLesson();
        $created = $this->getCourseLessonService()->createCourseLesson($lesson);

        $fields = array(
            'teachAim' => '教学目的',
        );
        $this->getCourseLessonService()->updateCourseLesson($created['id'] + 1, $fields);
    }

    protected function mockCourseLesson($lesson = array())
    {
        $default = array(
            'courseId' => 1,
            'number' => 1,
            'seq' => 1,
            'title' => '课次的title',
        );

        return array_merge($default, $lesson);
    }

    protected function getCourseLessonService()
    {
        return $this->createService('CustomBundle:Course:CourseLessonService');
    }
}
