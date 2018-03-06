<?php

namespace Tests\Unit\CustomBundle\Score;

use Biz\BaseTestCase;

class TeacherScoreServiceTest extends BaseTestCase
{
    public function testCreateTeacherScore()
    {
        $teacherScore = $this->mockTeacherScore();

        $created = $this->getTeacherScoreService()->createTeacherScore($teacherScore);

        $this->assertEquals($created['courseId'], $teacherScore['courseId']);
        $this->assertEquals($created['term'], $teacherScore['term']);
        $this->assertEquals($created['source'], $teacherScore['source']);
    }

    /**
     * @expectedException \Codeages\Biz\Framework\Service\Exception\InvalidArgumentException
     */
    public function testCreateTeacherScoreWithoutRequiredFields()
    {
        $teacherScore = array(
            'courseId' => 1,
            'term' => 1,
        );

        $created = $this->getTeacherScoreService()->createTeacherScore($teacherScore);
    }

    protected function mockTeacherScore($teacherScore = array())
    {
        $default = array(
            'courseId' => 1,
            'lessonId' => 1,
            'type' => 1,
            'term' => 1,
            'score' => 1,
            'source' => 'signIn',
            'userId' => 1,
            'remark' => '1',
        );

        return array_merge($default, $teacherScore);
    }

    private function getTeacherScoreService()
    {
        return $this->createService('CustomBundle:Score:TeacherScoreService');
    }
}
