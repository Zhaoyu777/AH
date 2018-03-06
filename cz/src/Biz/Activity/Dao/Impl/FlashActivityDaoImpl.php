<?php

namespace Biz\Activity\Dao\Impl;

use Biz\Activity\Dao\FlashActivityDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class FlashActivityDaoImpl extends GeneralDaoImpl implements FlashActivityDao
{
    protected $table = 'activity_flash';

    public function declares()
    {
        return array(
            'timestamps' => array('createdTime', 'updatedTime'),
        );
    }

    public function findByIds($Ids)
    {
        return $this->findInField('id', $Ids);
    }
}
