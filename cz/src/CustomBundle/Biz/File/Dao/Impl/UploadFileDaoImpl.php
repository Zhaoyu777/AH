<?php

namespace CustomBundle\Biz\File\Dao\Impl;

use CustomBundle\Biz\File\Dao\UploadFileDao;
use Biz\File\Dao\Impl\UploadFileDaoImpl as BaseUploadFileDaoImpl;

class UploadFileDaoImpl extends BaseUploadFileDaoImpl implements UploadFileDao
{
    public function countByTimeRangeAndOrgCodeGroupType($startTime, $endTime, $orgCode)
    {
        $sql = "SELECT {$this->table}.type, COUNT({$this->table}.id) as count FROM {$this->table} ";
        $sql .= "LEFT JOIN user ON {$this->table}.createdUserId = user.id WHERE ";

        $sql .= "{$this->table}.createdTime >= ? AND {$this->table}.createdTime < ? AND user.orgCode LIKE ? GROUP BY {$this->table}.type;";

        return $this->db()->fetchAll($sql, array($startTime, $endTime, "{$orgCode}%")) ? : array();
    }

    public function declares()
    {
        return array(
            'conditions' => array(
                'etag = :etag',
                'targetType = :targetType',
                'targetType IN ( :targetTypes )',
                'useType IN ( :useTypes)',
                'useType LIKE :useTypeLike',
                'globalId = :globalId',
                'globalId IN ( :globalIds )',
                'globalId <> ( :existGlobalId )',
                'targetType <> :noTargetType',
                'targetType NOT IN (:noTargetTypes)',
                'convertStatus = :convertStatus',
                'targetId = :targetId',
                'status = :status',
                'isPublic = :isPublic',
                'targetId IN ( :targets )',
                'type = :type',
                'type IN ( :types )',
                'storage = :storage',
                'filename LIKE :filenameLike',
                'id IN ( :ids )',
                'createdTime >= :startDate',
                'createdTime < :endDate',
                'usedCount >= :startCount',
                'usedCount < :endCount',
                'createdUserId IN ( :createdUserIds )',
                'createdUserId = :createdUserId',
                'id IN ( :idsOr )',
                'termCode = :termCode'
            ),
            'serializes' => array(
                'metas2' => 'json',
                'metas' => 'json',
                'convertParams' => 'json',
            ),
            'orderbys' => array(
                'createdTime',
                'id',
            ),
        );
    }
}
