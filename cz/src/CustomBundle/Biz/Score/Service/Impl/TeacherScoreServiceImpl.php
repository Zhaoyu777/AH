<?php

namespace CustomBundle\Biz\Score\Service\Impl;

use Biz\BaseService;
use AppBundle\Common\ArrayToolkit;
use Codeages\Biz\Framework\Event\Event;
use CustomBundle\Biz\Score\Service\TeacherScoreService;

class TeacherScoreServiceImpl extends BaseService implements TeacherScoreService
{
    public function tryGainScore($teacherScore)
    {
        $user = $this->getCurrentUser();

        if (!$user->isLogin()) {
            throw $this->createAccessDeniedException('未登陆');
        }

        if (!$user->isTeacher()) {
            throw $this->createAccessDeniedException('非教师');
        }
    }

    public function createTeacherScore($teacherScore)
    {
        if (!ArrayToolkit::requireds($teacherScore, array('courseId', 'term', 'source'))) {
            throw $this->createInvalidArgumentException('Lack of required fields');
        }
        $user = $this->getCurrentUser();
        $teacherScore['userId'] = $user['id'];

        $teacherScore = ArrayToolkit::parts($teacherScore, array(
            'courseId',
            'term',
            'userId',
            'score',
            'type',
            'lessonId',
            'source',
            'remark',
            'taskId',
        ));

        $created = $this->getTeacherScoreDao()->create($teacherScore);

        return $created;
    }

    public function findTeacherScoresByTermAndUserId($term, $userId)
    {
        return $this->getTeacherScoreDao()->findByTermAndUserId($term, $userId);
    }

    public function isGainScoreByLessonIdAndSource($lessonId, $source)
    {
        $user = $this->getCurrentUser();

        $score = $this->getTeacherScoreDao()->getByLessonAndUserIdAndSource($lessonId, $user['id'], $source);

        return !empty($score);
    }

    public function getSumScoreByTermAndUserId($term, $userId)
    {
        return $this->getTeacherScoreDao()->sumScoreByTermAndUserId($term, $userId);
    }

    public function findTeacherScoresByLessonIdAndUserId($lessonId, $userId)
    {
        return $this->getTeacherScoreDao()->findByLessonIdAndUserId($lessonId, $userId);
    }

    public function countTeacherScores($conditions)
    {
        return $this->getTeacherScoreDao()->count($conditions);
    }

    public function searchTeacherScores($conditions, $orderBy, $start, $limit)
    {
        return $this->getTeacherScoreDao()->search($conditions, $orderBy, $start, $limit);
    }

    public function getSumScoreByCourseSetId($courseSetId)
    {
        $courses = $this->getCourseService()->findCoursesByCourseSetId($courseSetId);
        $courseIds = ArrayToolkit::column($courses, 'id');

        return $this->getTeacherScoreDao()->sumScoreByCourseSetId($courseIds);
    }

    protected function getCourseService()
    {
        return $this->createService('CustomBundle:Course:CourseService');
    }

    protected function getTeacherScoreDao()
    {
        return $this->createDao('CustomBundle:Score:TeacherScoreDao');
    }
}
