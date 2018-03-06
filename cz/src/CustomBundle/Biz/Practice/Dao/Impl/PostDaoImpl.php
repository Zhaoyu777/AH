<?php

namespace CustomBundle\Biz\Practice\Dao\Impl;

use CustomBundle\Biz\Practice\Dao\PostDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class PostDaoImpl extends GeneralDaoImpl implements PostDao
{
    protected $table = 'activity_practice_post';

    public function deleteByContentId($contentId)
    {
        return $this->db()->delete($this->table, array(
            'contentId' => $contentId
        ));
    }

    public function findByContentId($contentId)
    {
        return $this->findByFields(array('contentId' => $contentId));
    }

    public function declares()
    {
        return array(
            'serializes' => array(),
            'orderbys' => array(),
            'timestamps' => array('createdTime'),
            'conditions' => array(),
        );
    }
}
