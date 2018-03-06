<?php

namespace CustomBundle\Biz\Testpaper\Dao\Impl;

use Biz\Testpaper\Dao\Impl\TestpaperDaoImpl as BaseDaoImpl;

class TestpaperDaoImpl extends BaseDaoImpl
{
    public function findByCourseId($courseId)
    {
        return $this->findByFields(array(
            'courseId' => $courseId
        ));
    }

    public function searchTestpapersOrderByLessonNumAndTaskId($testpaperIds, $start, $limit)
    {
        if (empty($testpaperIds)) {
            return array();
        }
        $marks = str_repeat('?,', count($testpaperIds) - 1).'?';

        $sql = "SELECT t.*  FROM `testpaper_v8` t  
        LEFT JOIN `activity` a ON a.mediaId = t.id 
        LEFT JOIN `course_task` ct ON a.id = ct.activityId
        LEFT JOIN `czie_course_lesson_task` cc ON ct.id = cc.taskId
        WHERE t.status = 'open' AND t.type = 'homework' AND t.id IN ({$marks}) and a.mediaType='homework'
        ORDER BY cc.lessonId ASC, cc.taskId ASC  LIMIT ${start} , ${limit}";

        

        return $this->db()->fetchAll($sql, $testpaperIds) ?: array();
    }

    public function findOpenTestpapersByLessonIds($lessonIds)
    {
        $marks = str_repeat('?,', count($lessonIds) - 1).'?';

        $sql = "SELECT * FROM {$this->table} WHERE lessonId IN ({$marks}) AND status = 'open'";

        return $this->db()->fetchAll($sql, $lessonIds);
    }
}
