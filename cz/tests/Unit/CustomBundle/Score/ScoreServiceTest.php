<?php

namespace Tests\Unit\CustomBundle\Score;

use Biz\BaseTestCase;

class ScoreServiceTest extends BaseTestCase
{
    public function testCreateScore()
    {
        $score = $this->mockScore();

        $created = $this->getScoreService()->createScore($score);

        $this->assertEquals($created['courseId'], $score['courseId']);
        $this->assertEquals($created['score'], $score['score']);
        $this->assertEquals($created['remark'], $score['remark']);
    }

    /**
     * @expectedException \Codeages\Biz\Framework\Service\Exception\InvalidArgumentException
     */
    public function testCreateScoreWithoutRequiredFields()
    {
        $score = array(
            'courseId' => 1,
            'term' => 1,
            'userId' => 1,
        );

        $created = $this->getScoreService()->createScore($score);
    }

    protected function mockScore($score = array())
    {
        $default = array(
            'courseId' => 1,
            'term' => 1,
            'userId' => 1,
            'score' => 1,
            'targetType' => 'rollcall',
            'targetId' => 1,
            'remark' => 'user #1 rollcall remark',
        );

        return array_merge($default, $score);
    }

    private function getScoreService()
    {
        return $this->createService('CustomBundle:Score:ScoreService');
    }
}
