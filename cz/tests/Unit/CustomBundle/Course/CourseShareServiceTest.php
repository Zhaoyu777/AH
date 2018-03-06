<?php

namespace Tests\Unit\CustomBundle\Course;

use Biz\BaseTestCase;

class CourseShareServiceTest extends BaseTestCase
{
    /**
     * @expectedException \Codeages\Biz\Framework\Service\Exception\InvalidArgumentException
     */
    public function testCreateCourseShareWithoutRequiredFields()
    {
        $share = array(
            'courseId' => 1,
        );

        $created = $this->getCourseShareService()->createCourseShare($share);
    }

    protected function mockCourseShare($courseId = 1)
    {
        return array(
            'courseId' => $courseId,
            'toUserId' => 2,
        );
    }

    protected function getCourseShareService()
    {
        return $this->createService('CustomBundle:Course:CourseShareService');
    }
}
