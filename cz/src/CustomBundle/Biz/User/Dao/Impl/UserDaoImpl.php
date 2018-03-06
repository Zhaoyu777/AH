<?php

namespace CustomBundle\Biz\User\Dao\Impl;

use Biz\User\Dao\Impl\UserDaoImpl as BaseUserDaoImpl;
use CustomBundle\Biz\User\Dao\UserDao;

class UserDaoImpl extends BaseUserDaoImpl implements UserDao
{
    public function searchAllUsers(array $conditions, array $orderBy, $start, $limit)
    {
        $conditions = array_filter($conditions);

        $builder = $this->_createQueryBuilder($conditions)
                        ->setFirstResult($start)
                        ->setMaxResults($limit)
                        ->select('u.*');
        foreach ($orderBy ?: array() as $order => $sort) {
            $builder->addOrderBy($order, $sort);
        }

        return $builder->execute()->fetchAll();
    }

    public function countAllUsers($conditions)
    {
        $conditions = array_filter($conditions);

        $builder = $this->_createQueryBuilder($conditions)
                        ->select('count(u.id)');

        return $builder->execute()->fetchColumn();
    }

    public function countTeachersByCode($code)
    {
        $code = $code.'%';
        $sql = "SELECT count(*) FROM {$this->table} WHERE roles LIKE '%ROLE_TEACHER%' AND orgCode LIKE ?";

        return $this->db()->fetchColumn($sql, array($code));
    }

    protected function _createQueryBuilder($conditions)
    {
        if (isset($conditions['queryField'])) {
            $conditions['queryField'] = "%{$conditions['queryField']}%";
        }

        if (isset($conditions['roles'])) {
            $conditions['roles'] = "%{$conditions['roles']}%";
        }

        if (isset($conditions['excludeRole'])) {
            $conditions['excludeRole'] = "%{$conditions['excludeRole']}%";
        }

        if (isset($conditions['orgCode'])) {
            $conditions['orgCode'] = "{$conditions['orgCode']}%";
        }

        $builder = $this->getQueryBuilder($conditions)
            ->from($this->table, 'u')
            ->leftJoin('u', 'user_profile', 'p', 'u.id = p.id')
            ->andWhere('(u.nickname LIKE :queryField) or (p.truename LIKE :queryField)')
            ->andWhere('u.id NOT IN ( :excludeIds )')
            ->andWhere('u.roles LIKE :roles')
            ->andWhere('u.roles = :role')
            ->andWhere('u.roles NOT LIKE :excludeRole')
            ->andWhere('u.id IN ( :userIds )')
            ->andWhere('u.orgCode LIKE :orgCode');

        return $builder;
    }

    public function declares()
    {
        return array(
            'serializes' => array(
                'roles' => 'delimiter',
            ),
            'orderbys' => array(
                'id',
                'createdTime',
                'updatedTime',
                'promotedTime',
                'promoted',
                'promotedSeq',
                'nickname',
                'loginTime',
            ),
            'timestamps' => array(
                'createdTime',
                'updatedTime',
            ),
            'conditions' => array(
                'mobile = :mobile',
                'promoted = :promoted',
                'roles LIKE :roles',
                'roles = :role',
                'UPPER(nickname) LIKE :nickname',
                'id =: id',
                'loginIp = :loginIp',
                'createdIp = :createdIp',
                'approvalStatus = :approvalStatus',
                'UPPER(email) LIKE :email',
                'level = :level',
                'createdTime >= :startTime',
                'createdTime <= :endTime',
                'updatedTime >= :updatedTime_GE',
                'approvalTime >= :startApprovalTime',
                'approvalTime <= :endApprovalTime',
                'loginTime >= :loginStartTime',
                'loginTime <= :loginEndTime',
                'locked = :locked',
                'level >= :greatLevel',
                'UPPER(verifiedMobile) LIKE :verifiedMobile',
                'type LIKE :type',
                'id IN ( :userIds)',
                'inviteCode = :inviteCode',
                'inviteCode != :NoInviteCode',
                'id NOT IN ( :excludeIds )',
                'orgCode PRE_LIKE :likeOrgCode',
                'orgCode = :orgCode',
            ),
        );
    }
}
