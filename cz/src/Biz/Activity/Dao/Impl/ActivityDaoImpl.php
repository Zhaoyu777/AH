<?php

namespace Biz\Activity\Dao\Impl;

use Biz\Activity\Dao\ActivityDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class ActivityDaoImpl extends GeneralDaoImpl implements ActivityDao
{
    protected $table = 'activity';

    public function findByCourseId($courseId)
    {
        $sql = "SELECT * FROM {$this->table()} WHERE fromCourseId = ?";

        return $this->db()->fetchAll($sql, array($courseId)) ?: array();
    }

    public function findByIds($ids)
    {
        return $this->findInField('id', $ids);
    }

    public function getByCopyIdAndCourseSetId($copyId, $courseSetId)
    {
        return $this->getByFields(array('copyId' => $copyId, 'fromCourseSetId' => $courseSetId));
    }

    public function findSelfVideoActivityByCourseIds($courseIds)
    {
        if (empty($courseIds)) {
            return array();
        }
        $sql = "select  a.*,  c.mediaId as fileId  from activity a left join activity_video c on a.mediaId = c.id where a.mediaType='video' and c.mediaSource='self' and a.fromCourseId in (".implode(',', $courseIds).')';

        return $this->db()->fetchAll($sql, array());
    }

    public function declares()
    {
        $declares['conditions'] = array(
            'fromCourseId = :fromCourseId',
            'mediaType = :mediaType',
            'fromCourseId IN (:courseIds)',
            'mediaType IN (:mediaTypes)',
            'mediaId = :mediaId',
        );

        return $declares;
    }

    public function findOverlapTimeActivitiesByCourseId($courseId, $newStartTime, $newEndTime, $excludeId = null)
    {
        $sql = "SELECT * FROM {$this->table} WHERE fromCourseId = ? AND (( startTime >= ? AND startTime <= ? ) OR ( startTime <= ? AND endTime >= ? ) OR ( endTime >= ? AND endTime <= ? ))";

        if ($excludeId) {
            $excludeId = intval($excludeId);
            $sql .= " AND id <> {$excludeId}";
        }

        return $this->db()->fetchAll($sql, array($courseId, $newStartTime, $newEndTime, $newStartTime, $newEndTime, $newStartTime, $newEndTime));
    }
}
