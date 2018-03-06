<?php

namespace CustomBundle\Biz\Activity\Dao\Impl;

use CustomBundle\Biz\Activity\Dao\QuestionnaireActivityDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class QuestionnaireActivityDaoImpl extends GeneralDaoImpl implements QuestionnaireActivityDao
{
    protected $table = 'activity_questionnaire';

    public function findByIds($ids)
    {
        return $this->findInField('id', $ids);
    }

    public function declares()
    {
        return array(
            'serializes' => array(),
            'orderbys'   => array(),
            'timestamps' => array('createdTime', 'updatedTime'),
            'conditions' => array(
                'mediaId = :mediaId',
            )
        );
    }
}
