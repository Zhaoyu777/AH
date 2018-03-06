<?php

namespace Biz\User\Dao\Impl;

use Biz\User\Dao\UserProfileDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;
use Codeages\Biz\Framework\Service\Exception\InvalidArgumentException;

class UserProfileDaoImpl extends GeneralDaoImpl implements UserProfileDao
{
    protected $table = 'user_profile';

    public function findByIds(array $ids)
    {
        return $this->findInField('id', $ids);
    }

    public function dropFieldData($fieldName)
    {
        $fieldNames = array(
            'intField1',
            'intField2',
            'intField3',
            'intField4',
            'intField5',
            'dateField1',
            'dateField2',
            'dateField3',
            'dateField4',
            'dateField5',
            'floatField1',
            'floatField2',
            'floatField3',
            'floatField4',
            'floatField5',
            'textField1',
            'textField2',
            'textField3',
            'textField4',
            'textField5',
            'textField6',
            'textField7',
            'textField8',
            'textField9',
            'textField10',
            'varcharField1',
            'varcharField2',
            'varcharField3',
            'varcharField4',
            'varcharField5',
            'varcharField6',
            'varcharField7',
            'varcharField8',
            'varcharField9',
            'varcharField10', );

        if (!in_array($fieldName, $fieldNames)) {
            throw new InvalidArgumentException('Invalid Arguments');
        }

        $sql = "UPDATE {$this->table} set {$fieldName} =null ";
        $result = $this->db()->exec($sql);

        return $result;
    }

    public function findDistinctMobileProfiles($start, $limit)
    {
        $sql = "SELECT * FROM {$this->table} WHERE `mobile` <> '' GROUP BY `mobile` ORDER BY `id` ASC";
        $sql = $this->sql($sql, array(), $start, $limit);

        return $this->db()->fetchAll($sql);
    }

    protected function createQueryBuilder($conditions)
    {
        if (isset($conditions['mobile'])) {
            $conditions['mobile'] = "%{$conditions['mobile']}%";
        }

        if (isset($conditions['qq'])) {
            $conditions['qq'] = "{$conditions['qq']}%";
        }

        if (isset($conditions['keywordType']) && isset($conditions['keyword']) && $conditions['keywordType'] == 'truename') {
            $conditions['truename'] = "%{$conditions['keyword']}%";
        }

        if (isset($conditions['keywordType']) && isset($conditions['keyword']) && $conditions['keywordType'] == 'idcard') {
            $conditions['idcard'] = "%{$conditions['keyword']}%";
        }

        return parent::createQueryBuilder($conditions);
    }

    public function declares()
    {
        return array(
            'orderbys' => array('id'),
            'conditions' => array(
                'mobile LIKE :mobile',
                'truename LIKE :truename',
                'idcard LIKE :idcard',
                'id IN (:ids)',
                'mobile = :tel',
                'mobile <> :mobileNotEqual',
                'qq LIKE :qq',
            ),
        );
    }
}
