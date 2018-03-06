<?php

namespace CustomBundle\Biz\DisplayWall\Dao\Impl;

use CustomBundle\Biz\DisplayWall\Dao\PostDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class PostDaoImpl extends GeneralDaoImpl implements PostDao
{
    protected $table = 'activity_display_wall_post';

    public function findByContentId($contentId)
    {
        return $this->findByFields(array('contentId' => $contentId));
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
            'timestamps' => array('createdTime', 'updatedTime'),
            'conditions' => array()
        );
    }
}
