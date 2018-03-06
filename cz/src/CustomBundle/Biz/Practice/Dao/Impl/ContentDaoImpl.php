<?php

namespace CustomBundle\Biz\Practice\Dao\Impl;

use CustomBundle\Biz\Practice\Dao\ContentDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class ContentDaoImpl extends GeneralDaoImpl implements ContentDao
{
    protected $table = 'activity_practice_content';

    public function getByResultId($resultId)
    {
        return $this->getByFields(array('resultId' => $resultId));
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
            'orderbys' => array(),
            'timestamps' => array('createdTime'),
            'conditions' => array(),
        );
    }
}
