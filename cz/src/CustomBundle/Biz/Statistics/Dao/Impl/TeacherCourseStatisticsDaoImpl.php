<?php

namespace CustomBundle\Biz\Statistics\Dao\Impl;

use Codeages\Biz\Framework\Dao\GeneralDaoImpl;
use CustomBundle\Biz\Statistics\Dao\TeacherCourseStatisticsDao;

class TeacherCourseStatisticsDaoImpl extends GeneralDaoImpl implements TeacherCourseStatisticsDao
{
    protected $table = 'czie_teacher_course_statistics';

    public function getByUserId($userId)
    {
        return $this->getByFields(array('userId' => $userId));
    }

    public function getByUserIdAndTermCode($userId, $termCode)
    {
        return $this->getByFields(array('userId' => $userId, 'termCode' => $termCode));
    }

    public function getAvgAttendRateByAllCourse()
    {
        $sql = "SELECT AVG(studentAttendRate) FROM {$this->table} where courseLessonRate is not NULL";

        return $this->db()->fetchColumn($sql, array()) ? : 0;
    }

    public function getAvgLessonRateByAllCourse()
    {
        $sql = "SELECT AVG(lessonRate) FROM {$this->table} where courseLessonRate is not NULL";

        return $this->db()->fetchColumn($sql, array()) ? : 0;
    }

    public function declares()
    {
        return array(
            'orderbys'   => array(),
            'timestamps' => array('createdTime', 'updatedTime'),
            'conditions' => array(
                'userId = :userId',
                'courseLessonRate  = :courseLessonRate',
                'lessonRate  = :lessonRate',
                'studentAttendRate = :studentAttendRate',
                'taskOuterCompletionRate  = :taskOuterCompletionRate',
                'taskInCompletionRate = :taskInCompletionRate',
                'loginDays = :loginDays',
                'analysisNum = :analysisNum',
                'homeworkNum = :homeworkNum',
                'resourcesNum = :resourcesNum',
                'resourcesIncreaseNum = :resourcesIncreaseNum',
                'resourcesQuoteNum = :resourcesQuoteNum',
                'termCode = :termCode',
                'courseLessonRate >= :gtcourseLessonRate',
                'lessonRate >= :gtlessonRate',
                'studentAttendRate >= :gtstudentAttendRate',
                'taskOuterCompletionRate >= :gttaskOuterCompletionRate',
                'taskInCompletionRate >= :gttaskInCompletionRate',
                'loginDays >= :gtloginDays',
                'analysisNum >= :gtanalysisNum',
                'homeworkNum >= :gthomeworkNum',
                'resourcesNum >= :gtresourcesNum',
                'resourcesIncreaseNum >= :gtresourcesIncreaseNum',
                'resourcesQuoteNum >= :gtresourcesQuoteNum',
            )
        );
    }
}
