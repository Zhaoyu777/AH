<?php

namespace Tests\Unit\CustomBundle\Lesson;

use Biz\BaseTestCase;

class EvaluationServiceRest extends BaseTestCase
{
    /**
     * @expectedException \Codeages\Biz\Framework\Service\Exception\InvalidArgumentException
     */
    public function testCreateEvaluationWithoutRequiredFields()
    {
        $evaluation = array(
            'courseId' => 1,
            'lessonId' => 1,
            'remark' => 1,
        );

        $created = $this->getEvaluationService()->createEvaluation($evaluation);
    }

    /**
     * @expectedException \Codeages\Biz\Framework\Service\Exception\AccessDeniedException
     */
    public function testTryEvaluationLessonNotEnd()
    {
        $evaluation = array(
            'courseId' => 1,
            'lessonId' => 1,
        );

        $this->getEvaluationService()->tryEvaluate($evaluation);
    }

    public function mockEvaluation($fields = array())
    {
        $default = array(
            'courseId' => 1,
            'lessonId' => 1,
            'remark' => 1,
            'score' => 1,
        );

        return array_merge($default, $fields);
    }

    protected function getEvaluationService()
    {
        return $this->createService('CustomBundle:Lesson:EvaluationService');
    }
}
