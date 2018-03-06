<?php

namespace ApiBundle\Api\Resource\Announcement;

use ApiBundle\Api\Annotation\ApiConf;
use ApiBundle\Api\ApiRequest;
use ApiBundle\Api\Exception\ErrorCode;
use ApiBundle\Api\Resource\AbstractResource;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Announcement extends AbstractResource
{
    /**
     * @ApiConf(isRequiredAuth=false)
     */
    public function search(ApiRequest $request)
    {
        $startTime = $request->query->get('startTime', 0);
        $conditions = array(
            'targetType' => 'global',
            'startTime_GT' => $startTime,
        );

        list($offset, $limit) = $this->getOffsetAndLimit($request);
        $announcements = $this->getAnnouncementService()->searchAnnouncements(
            $conditions,
            array('createdTime' => 'DESC'),
            $offset,
            $limit
        );

        $total = $this->getAnnouncementService()->countAnnouncements($conditions);

        return $this->makePagingObject($announcements, $total, $offset, $limit);
    }

    protected function getAnnouncementService()
    {
        return $this->service('Announcement:AnnouncementService');
    }
}