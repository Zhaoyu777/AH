<?php

namespace CustomBundle\Biz\DisplayWall\Dao\Impl;

use CustomBundle\Biz\DisplayWall\Dao\LikeDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class LikeDaoImpl extends GeneralDaoImpl implements LikeDao
{
    protected $table = 'activity_display_wall_like';

    public function deleteByContentIdAndUserId($contentId, $userId)
    {
        return $this->db()->delete($this->table, array('contentId' => $contentId, 'userId' => $userId));
    }

    public function getByContentIdAndUserId($contentId, $userId)
    {
        return $this->getByFields(array('contentId' => $contentId, 'userId' => $userId));
    }

    public function findByContentIdsAndUserId($contentIds, $userId)
    {
        $marks = str_repeat('?,', count($contentIds) - 1).'?';
        $sql = "SELECT * FROM {$this->table} WHERE `contentId` IN ({$marks}) AND userId = ? ORDER BY `createdTime`;";
        $fields = array_merge($contentIds, array($userId));

        return $this->db()->fetchAll($sql, $fields);
    }

    public function deleteByContentId($contentId)
    {
        return $this->db()->delete($this->table(), array('contentId' => $contentId));
    }

    public function declares()
    {
        return array(
            'serializes' => array(),
            'orderbys'   => array(),
            'timestamps' => array('createdTime'),
            'conditions' => array()
        );
    }
}
