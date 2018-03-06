<?php

namespace CustomBundle\Biz\Activity\Service\Impl;

use AppBundle\Common\ArrayToolkit;
use Codeages\Biz\Framework\Event\Event;
use Biz\Activity\Service\Impl\ActivityServiceImpl as BaseActivityServiceImpl;

class ActivityServiceImpl extends BaseActivityServiceImpl
{
    protected function filterFields($fields)
    {
        $fields = ArrayToolkit::parts(
            $fields,
            array(
                'title',
                'remark',
                'mediaId',
                'mediaType',
                'content',
                'about',
                'duration',
                'length',
                'score',
                'fromCourseId',
                'fromCourseSetId',
                'fromUserId',
                'startTime',
                'endTime',
            )
        );

        if (!empty($fields['startTime']) && !empty($fields['length']) && $fields['mediaType'] != 'testpaper') {
            $fields['endTime'] = $fields['startTime'] + $fields['length'] * 60;
        }

        if (empty($fields['mediaType'])) {
            unset($fields['mediaType']);
        }

        return $fields;
    }

    public function getActivityByMediaIdAndMediaType($mediaId, $mediaType)
    {
        return $this->getActivityDao()->getActivityByMediaIdAndMediaType($mediaId, $mediaType);
    }

    public function searchActivitysOrderByLessonNumAndTaskId($conditions, $start, $limit)
    {
        return $this->getActivityDao()->searchActivitysOrderByLessonNumAndTaskId($conditions, $start, $limit);
    }

    protected function getActivityDao()
    {
        return $this->createDao('CustomBundle:Activity:ActivityDao');
    }
}
