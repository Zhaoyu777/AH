<?php

namespace CustomBundle\Biz\Course\Dao\Impl;

use Biz\Course\Dao\Impl\CourseSetDaoImpl as BaseCourseSetDaoImpl;

class CourseSetDaoImpl extends BaseCourseSetDaoImpl
{
    public function getByUserIdAndCourseNo($userId, $courseNo)
    {
        return $this->getByFields(array(
            'userId'   => $userId,
            'courseNo' => $courseNo
        ));
    }

    public function countCourseSetsWithCourseNo($conditions)
    {
        $builder = $this->createQueryBuilder($conditions)
            ->select('COUNT(*)');

        if (!empty($conditions['courseType']) && $conditions['courseType'] == 'school') {
            $builder->andStaticWhere('courseNo is not null');
        } elseif (!empty($conditions['courseType']) && $conditions['courseType'] == 'custom') {
            $builder->andStaticWhere('courseNo is null');
        }

        return (int) $builder->execute()->fetchColumn(0);
    }



    public function searchWithCourseNo($conditions, $orderBys, $start, $limit)
    {
        $builder = $this->createQueryBuilder($conditions)
            ->select('*')
            ->setFirstResult($start)
            ->setMaxResults($limit);

        $declares = $this->declares();
        foreach ($orderBys ?: array() as $order => $sort) {
            $this->checkOrderBy($order, $sort, $declares['orderbys']);
            $builder->addOrderBy($order, $sort);
        }

        if (!empty($conditions['courseType']) && $conditions['courseType'] == 'school') {
            $builder->andStaticWhere('courseNo is not null');
        } elseif (!empty($conditions['courseType']) && $conditions['courseType'] == 'custom') {
            $builder->andStaticWhere('courseNo is null');
        }

        return $builder->execute()->fetchAll();
    }

    private function checkOrderBy($order, $sort, $allowOrderBys)
    {
        if (!in_array($order, $allowOrderBys, true)) {
            throw $this->createDaoException(
                sprintf("SQL order by field is only allowed '%s', but you give `{$order}`.", implode(',', $allowOrderBys))
            );
        }
        if (!in_array(strtoupper($sort), array('ASC', 'DESC'), true)) {
            throw $this->createDaoException("SQL order by direction is only allowed `ASC`, `DESC`, but you give `{$sort}`.");
        }
    }

    public function declares()
    {
        return array(
            'conditions'            => array(
                'id IN ( :ids )',
                'status = :status',
                'status <> :excludeStatus',
                'isVip = :isVip',
                'categoryId = :categoryId',
                'categoryId IN (:categoryIds)',
                'title LIKE :title',
                'creator LIKE :creator',
                'type = :type',
                'type <> :excludeType',
                'recommended = :recommended',
                'id NOT IN (:excludeIds)',
                'parentId = :parentId',
                'parentId > :parentId_GT',
                'createdTime >= :startTime',
                'createdTime <= :endTime',
                'discountId = :discountId',
                'minCoursePrice = :minCoursePrice',
                'maxCoursePrice > :maxCoursePrice_GT',
                'updatedTime >= updatedTime_GE',
                'updatedTime <= updatedTime_LE',
                'minCoursePrice = :price',
                'orgCode PRE_LIKE :likeOrgCode'
            ),
            'serializes'            => array(
                'goals'      => 'delimiter',
                'tags'       => 'delimiter',
                'audiences'  => 'delimiter',
                'teacherIds' => 'delimiter',
                'cover'      => 'json'
            ),
            'orderbys'              => array(
                'createdTime',
                'updatedTime',
                'recommendedSeq',
                'hitNum',
                'recommendedTime',
                'rating',
                'studentNum',
                'id'
            ),
            'timestamps'            => array(
                'createdTime', 'updatedTime'
            ),
            'wave_cahceable_fields' => array('hitNum')
        );
    }
}
