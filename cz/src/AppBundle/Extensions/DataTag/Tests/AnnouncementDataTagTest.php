<?php

namespace AppBundle\Extensions\DataTag\Test;

use Biz\Announcement\Service\AnnouncementService;
use Biz\BaseTestCase;
use AppBundle\Extensions\DataTag\AnnouncementDataTag;

class AnnouncementDataTagTest extends BaseTestCase
{
    /**
     * @expectedException \ServiceException
     */
    public function testCreateAnnouncementWithoutContent()
    {
        $this->getAnnouncementService()->createAnnouncement(array(
            'content' => 'Announcement1',
            'url' => 'http://',
            'userId' => '1',
            'startTime' => strtotime(date('Y-m-d H').':'.(intval(date('i') / 10) * 10).':0'),
            'endTime' => strtotime(date('Y-m-d H').':'.(intval(date('i') / 10) * 10).':0'),
            'targetId' => '1',
        ));
    }

    public function testGetData()
    {
        $this->getAnnouncementService()->createAnnouncement(array(
            'content' => 'Announcement1',
            'url' => 'http://',
            'userId' => '1',
            'startTime' => strtotime(date('Y-m-d H').':'.(intval(date('i') / 10) * 10).':0'),
            'endTime' => strtotime(date('Y-m-d H').':'.(intval(date('i') / 10) * 10).':0'),
            'targetType' => 'global',
            'targetId' => '1',
        ));

        $this->getAnnouncementService()->createAnnouncement(array(
            'content' => 'Announcement3',
            'url' => 'http://',
            'userId' => '1',
            'startTime' => strtotime(date('Y-m-d H').':'.(intval(date('i') / 10) * 10).':0'),
            'targetType' => 'global',
            'endTime' => strtotime(date('Y-m-d H').':'.(intval(date('i') / 10) * 10).':0'),
            'targetId' => '1',
        ));

        $this->getAnnouncementService()->createAnnouncement(array(
            'content' => 'Announcement3',
            'url' => 'http://',
            'userId' => '1',
            'startTime' => strtotime(date('Y-m-d H').':'.(intval(date('i') / 10) * 10).':0'),
            'endTime' => strtotime(date('Y-m-d H').':'.(intval(date('i') / 10) * 10).':0'),
            'targetType' => 'global',
            'targetId' => '1',
        ));

        $this->getAnnouncementService()->createAnnouncement(array(
            'content' => 'Announcement4',
            'url' => 'http://',
            'userId' => '1',
            'startTime' => strtotime(date('Y-m-d H').':'.(intval(date('i') / 10) * 10).':0'),
            'endTime' => strtotime(date('Y-m-d H').':'.(intval(date('i') / 10) * 10).':0'),
            'targetType' => 'global',
            'targetId' => '1',
        ));

        $this->getAnnouncementService()->createAnnouncement(array(
            'content' => 'Announcement5',
            'url' => 'http://',
            'userId' => '1',
            'startTime' => strtotime(date('Y-m-d H').':'.(intval(date('i') / 10) * 10).':0'),
            'endTime' => strtotime(date('Y-m-d H').':'.(intval(date('i') / 10) * 10).':0'),
            'targetType' => 'global',
            'targetId' => '1',
        ));
        $dataTag = new AnnouncementDataTag();
        $announcement = $dataTag->getData(array('count' => '5'));
        $this->assertEquals(5, count($announcement));
    }

    /**
     * @return AnnouncementService
     */
    private function getAnnouncementService()
    {
        return $this->getServiceKernel()->createService('Announcement:AnnouncementService');
    }
}
