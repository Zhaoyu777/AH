<?php

namespace CustomBundle\Biz\DisplayWall\Dao\Impl;

use CustomBundle\Biz\DisplayWall\Dao\ContentDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class ContentDaoImpl extends GeneralDaoImpl implements ContentDao
{
    protected $table = 'activity_display_wall_content';

    public function getLastByResultId($resultId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE `resultId` = ? ORDER BY `createdTime` DESC LIMIT 1;";

        return $this->db()->fetchAssoc($sql, array($resultId));
    }

    public function findByUserIds($userIds)
    {
        return $this->findInField('userId', $userIds);
    }

    public function findByResultId($resultId)
    {
        return $this->findByFields(array('resultId' => $resultId));
    }

    public function findByResultIds($resultIds)
    {
        return $this->findInField('resultId', $resultIds);
    }

    public function getByResultIdAndUserId($resultId, $userId)
    {
        return $this->getByFields(array(
            'resultId' => $resultId,
            'userId' => $userId,
        ));
    }

    public function declares()
    {
        return array(
            'serializes' => array(),
            'orderbys'   => array(),
            'timestamps' => array('createdTime', 'updatedTime'),
            'conditions' => array()
        );
    }
}
