<?php

namespace CustomBundle\Biz\Course\Dao\Impl;

use CustomBundle\Biz\Course\Dao\TermDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class TermDaoImpl extends GeneralDaoImpl implements TermDao
{
    protected $table = 'czie_term';

    public function getByShortCode($code)
    {
        return $this->getByFields(array('shortCode' => $code));
    }

    public function getCurrentTerm()
    {
        return $this->getByFields(array('current' => 1));
    }

    public function reset()
    {
        $sql = "UPDATE {$this->table()} SET current = false";
        return $this->db()->executeUpdate($sql);
    }

    public function findAll()
    {
        $sql = "SELECT * FROM {$this->table} WHERE `isVisible` = 1 ORDER BY longCode DESC;";

        return $this->db()->fetchAll($sql);
    }

    public function declares()
    {
        return array(
            'serializes' => array(),
            'orderbys'   => array('shortCode', 'longCode', 'current'),
            'timestamps' => array('createdTime', 'updatedTime'),
            'conditions' => array()
        );
    }
}
