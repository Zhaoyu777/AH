<?php

namespace CustomBundle\Biz\SignIn\Dao\Impl;

use CustomBundle\Biz\SignIn\Dao\SignInWarningDao;
use Codeages\Biz\Framework\Dao\AdvancedDaoImpl;

class SignInWarningDaoImpl extends AdvancedDaoImpl implements SignInWarningDao
{
    protected $table = 'zhkt_sign_in_warning';

    public function findWarningList($absentTimes)
    {
        $sql = "SELECT * FROM {$this->table} WHERE keepAbsentTimes >= ?";

        return $this->db()->fetchAll($sql, array($absentTimes)) ?: array();
    }

    public function waveByUserId(array $userId, array $diffs)
    {
        $sets = array_map(
            function ($name) {
                return "{$name} = {$name} + ?";
            },
            array_keys($diffs)
        );

        $marks = str_repeat('?,', count($userId) - 1).'?';

        $sql = "UPDATE {$this->table()} SET ".implode(', ', $sets)." WHERE userId IN ($marks)";

        return $this->db()->executeUpdate($sql, array_merge(array_values($diffs), $userId));
    }

    public function declares()
    {
        return array(
            'serializes' => array(),
            'orderbys' => array(),
            'timestamps' => array('updatedTime'),
            'conditions' => array(
            ),
        );
    }
}
