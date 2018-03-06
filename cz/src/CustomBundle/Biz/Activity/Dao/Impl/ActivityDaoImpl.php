<?php
namespace CustomBundle\Biz\Activity\Dao\Impl;

use Biz\Activity\Dao\Impl\ActivityDaoImpl as BaseActivityDaoImpl;

class ActivityDaoImpl extends BaseActivityDaoImpl
{
    public function getActivityByMediaIdAndMediaType($mediaId, $mediaType)
    {
        return $this->getByFields(array('mediaId' => $mediaId, 'mediaType' => $mediaType));
    }

    public function searchActivitysOrderByLessonNumAndTaskId($conditions, $start, $limit)
    {
        $sql = "SELECT a.* FROM `activity` a  
        LEFT JOIN `course_task` ct ON a.id = ct.activityId
        LEFT JOIN `czie_course_lesson_task` cc ON ct.id = cc.taskId
        WHERE a.fromCourseId = ? AND a.mediaType = 'practiceWork' 
        ORDER BY cc.lessonId ASC, ct.id ASC LIMIT ${start}, ${limit}";

        return $this->db()->fetchAll($sql, array($conditions['fromCourseId'])) ?: array();
    }

    public function declares()
    {
        return array(
            'serializes' => array('metas' => 'json'),
            'orderbys'   => array('createdTime'),
            'timestamps' => array('createdTime', 'updatedTime'),
            'conditions' => array(
                'fromCourseId = :fromCourseId',
                'mediaType = :mediaType',
                'fromCourseId IN (:courseIds)',
                'mediaType IN (:mediaTypes)',
                'mediaId = :mediaId',
            )
        );
    }
}
