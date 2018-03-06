<?php

namespace Biz\Course\Dao\Impl;

use Biz\Course\Dao\CourseSetDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class CourseSetDaoImpl extends GeneralDaoImpl implements CourseSetDao
{
    protected $table = 'course_set_v8';

    public function findCourseSetsByParentIdAndLocked($parentId, $locked)
    {
        return $this->findByFields(array('parentId' => $parentId, 'locked' => $locked));
    }

    public function findByIds(array $ids)
    {
        return $this->findInField('id', $ids);
    }

    public function findLikeTitle($title)
    {
        if (empty($title)) {
            $title = '';
        }
        $title = '%'.$title.'%';
        $sql = "SELECT * FROM {$this->table} WHERE title LIKE ?";

        return $this->db()->fetchAll($sql, array($title));
    }

    public function analysisCourseSetDataByTime($startTime, $endTime)
    {
        $conditions = array(
            'startTime' => $startTime,
            'endTime' => $endTime,
            'parentId' => 0,
        );
        $builder = $this->createQueryBuilder($conditions)
            ->select("COUNT(id) as count, from_unixtime(createdTime, '%Y-%m-%d') as date")
            ->from($this->table, $this->table)
            ->groupBy("from_unixtime(createdTime,'%Y-%m-%d')")
            ->addOrderBy('DATE', 'ASC');

        return $builder->execute()->fetchAll();
    }

    public function declares()
    {
        return array(
            'conditions' => array(
                'id IN ( :ids )',
                'id = :id',
                'status = :status',
                'isVip = :isVip',
                'categoryId = :categoryId',
                'categoryId IN (:categoryIds)',
                'title LIKE :title',
                'creator LIKE :creator',
                'type = :type',
                'recommended = :recommended',
                'id NOT IN (:excludeIds)',
                'parentId = :parentId',
                'parentId > :parentId_GT',
                'createdTime >= :startTime',
                'createdTime <= :endTime',
                'discountId = :discountId',
                'minCoursePrice = :minCoursePrice',
                'maxCoursePrice > :maxCoursePrice_GT',
                'updatedTime >= :updatedTime_GE',
                'updatedTime <= :updatedTime_LE',
                'minCoursePrice = :price',
                'orgCode PRE_LIKE :likeOrgCode',
            ),
            'serializes' => array(
                'goals' => 'delimiter',
                'tags' => 'delimiter',
                'audiences' => 'delimiter',
                'teacherIds' => 'delimiter',
                'cover' => 'json',
            ),
            'orderbys' => array(
                'createdTime',
                'updatedTime',
                'recommendedSeq',
                'hitNum',
                'recommendedTime',
                'rating',
                'studentNum',
                'id',
            ),
            'timestamps' => array(
                'createdTime', 'updatedTime',
            ),
            'wave_cahceable_fields' => array('hitNum'),
        );
    }
}
