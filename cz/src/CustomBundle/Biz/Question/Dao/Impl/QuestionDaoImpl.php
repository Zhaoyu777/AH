<?php

namespace CustomBundle\Biz\Question\Dao\Impl;

use Biz\Question\Dao\Impl\QuestionDaoImpl as BaseQuestionDaoImpl;

class QuestionDaoImpl extends BaseQuestionDaoImpl
{
    public function declares()
    {
        $declares['timestamps'] = array(
            'createdTime',
            'updatedTime',
        );

        $declares['orderbys'] = array(
            'createdTime',
            'updatedTime',
        );

        $declares['conditions'] = array(
            'id IN ( :ids )',
            'parentId = :parentId',
            'difficulty = :difficulty',
            'type = :type',
            'type IN ( :types )',
            'stem LIKE :stem',
            'subCount <> :subCount',
            'id NOT IN ( :excludeIds )',
            'courseId = :courseId',
            'courseId IN (:courseIds)',
            'courseSetId = :courseSetId',
            'courseSetId IN (:courseSetIds)',
            'lessonId = :lessonId',
            'lessonId >= :lessonIdGT',
            'lessonId <= :lessonIdLT',
            'lessonId IN ( :lessonIds)',
            'copyId = :copyId',
            'createdUserId = :createdUserId',
            'createdUserId = :userId',
            'copyId IN (:copyIds)',
            'parentId > :parentIdGT',
        );

        $declares['serializes'] = array(
            'answer' => 'json',
            'metas' => 'json',
        );

        return $declares;
    }
}
