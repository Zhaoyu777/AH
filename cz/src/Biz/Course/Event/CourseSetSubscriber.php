<?php

namespace Biz\Course\Event;

use Biz\Course\Service\CourseService;
use Codeages\Biz\Framework\Event\Event;
use Biz\Course\Service\CourseSetService;
use Codeages\PluginBundle\Event\EventSubscriber;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CourseSetSubscriber extends EventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            'courseSet.maxRate.update' => 'onCourseSetMaxRateUpdate',
            'courseSet.recommend' => 'onCourseSetRecommend',
            'courseSet.recommend.cancel' => 'onCourseSetCancelRecommend',
            'course-set.update' => 'onCourseSetUpdate',
        );
    }

    public function onCourseSetMaxRateUpdate(Event $event)
    {
        $subject = $event->getSubject();
        $courseSet = $subject['courseSet'];
        $maxRate = $subject['maxRate'];

        return $this->getCourseService()->updateMaxRateByCourseSetId($courseSet['id'], $maxRate);
    }

    public function onCourseSetRecommend(Event $event)
    {
        $courseSet = $event->getSubject();
        $fields = $event->getArguments();
        $this->getCourseService()->recommendCourseByCourseSetId($courseSet['id'], $fields);
    }

    public function onCourseSetCancelRecommend(Event $event)
    {
        $courseSet = $event->getSubject();
        $this->getCourseService()->cancelRecommendCourseByCourseSetId($courseSet['id']);
    }

    public function onCourseSetUpdate(Event $event)
    {
        $courseSet = $event->getSubject();

        if (empty($courseSet['categoryId'])) {
            return;
        }

        $this->getCourseService()->updateCategoryByCourseSetId($courseSet['id'], $courseSet['categoryId']);
    }

    /**
     * @return CourseSetService
     */
    protected function getCourseSetService()
    {
        return $this->getBiz()->service('Course:CourseSetService');
    }

    /**
     * @return CourseService
     */
    protected function getCourseService()
    {
        return $this->getBiz()->service('Course:CourseService');
    }
}
