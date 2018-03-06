<?php

namespace CustomBundle\Biz\RandomTestpaper\Dao\Impl;

use CustomBundle\Biz\RandomTestpaper\Dao\RandomTestpaperItemDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class RandomTestpaperItemDaoImpl extends GeneralDaoImpl implements RandomTestpaperItemDao
{
    protected $table = 'random_testpaper_item';

    public function findByTestId($testId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE `testId` = ? ORDER BY `seq` ASC";

        return $this->db()->fetchAll($sql, array($testId)) ? :array();
    }

    public function declares()
    {
        return array(
            'serializes' => array('answer' => 'json'),
            'orderbys'   => array(),
            'timestamps' => array('createdTime', 'updatedTime'),
            'conditions' => array(
                'createdTime >= :raceCreatedTime',
            ),
        );
    }
}
