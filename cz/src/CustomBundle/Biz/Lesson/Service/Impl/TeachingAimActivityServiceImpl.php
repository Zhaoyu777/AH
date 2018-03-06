<?php

namespace CustomBundle\Biz\Lesson\Service\Impl;

use Biz\BaseService;
use CustomBundle\Biz\Lesson\Service\TeachingAimActivityService;
use AppBundle\Common\ArrayToolkit;
use Codeages\Biz\Framework\Event\Event;

class TeachingAimActivityServiceImpl extends BaseService implements TeachingAimActivityService
{
    public function findByActivityId($activityId)
    {
        return $this->getTeachingAimActivityDao()->findRelationsByActivityId($activityId);
    }

    public function batchCreate($relations)
    {
        if (empty($relations)) {
            return;
        }

        foreach ($relations as $relation) {
            if (!ArrayToolkit::requireds($relation, array('courseId', 'aimId', 'activityId', 'teacherId'))) {
                throw $this->createInvalidArgumentException('Lack of required fields');
            }
        }

        return $this->getTeachingAimActivityDao()->batchCreate($relations);
    }

    public function connectAims($activityId, $lessonId, $aimIds)
    {
        $this->getCourseLessonService()->tryManageCourseLesson($lessonId);
        
        $this->canConnectAimIds($activityId, $lessonId, $aimIds);

        $this->deleteByActivityId($activityId);

        return $this->batchCreate($this->buildRelations($activityId, $lessonId, $aimIds));
    }

    protected function canConnectAimIds($activityId, $lessonId, $aimIds)
    {
        if (count($aimIds) > self::CONNECTED_AIMS_MAX) {
            throw new \Exception("一个活动最多关联20个教学目标");
        }

        if ($this->isLessonEnded($lessonId)) {
            throw new \Exception("该课次已经下课，不能修改关联的教学目标");
        }

        return;
    }

    protected function isLessonEnded($lessonId)
    {
        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonId);

        if ($lesson['status'] == 'teached') {
            return true;
        }
        
        return false;
    }

    protected function buildRelations($activityId, $lessonId, $aimIds)
    {
        if (empty($aimIds)) {
            return array();
        }

        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonId);
        $course = $this->getCourseService()->getCourse($lesson['courseId']);
        $courseSet = $this->getCourseSetService()->getCourseSet($course['courseSetId']);
        $user = $this->getCurrentUser();

        $relations = array();
        foreach ($aimIds as $aimId) {
            $relations[] = array(
                'lessonId' => $lessonId,
                'activityId' => $activityId,
                'aimId' => $aimId,
                'courseId' => $course['id'],
                'orgCode' => $courseSet['orgCode'],
                'teacherId' => $user['id'],
                'termCode' => $course['termCode'],
            );
        }

        return $relations;
    }

    public function calcCourseFinishedRate($courseId)
    {
        $currentTerm = $this->getCourseService()->getCurrentTerm();

        $courseCurrentTermOwnedAims = $this->getTeachingAimDao()->findAimsByCourseIdAndTermCode($courseId, $currentTerm['shortCode']);
        $courseCurrentTermConnectedAims = $this->getTeachingAimActivityDao()->findRelationsByCourseIdAndTermCode($courseId, $currentTerm['shortCode']);

        if (empty($courseCurrentTermOwnedAims)) {
            return 0;
        }

        return $this->formatData(count($courseCurrentTermConnectedAims) / count($courseCurrentTermOwnedAims));
    }

    public function calcLessonFinishedRate($lessonId)
    {
        $currentTerm = $this->getCourseService()->getCurrentTerm();

        $courseCurrentTermOwnedAims = $this->getTeachingAimDao()->findAimsByLessonIdAndTermCode($lessonId, $currentTerm['shortCode']);
        $courseCurrentTermConnectedAims = $this->getTeachingAimActivityDao()->findRelationsByLessonIdAndTermCode($lessonId, $currentTerm['shortCode']);

        if (empty($courseCurrentTermOwnedAims)) {
            return null ;
        }

        return $this->formatData(count($courseCurrentTermConnectedAims) / count($courseCurrentTermOwnedAims));
    }

    public function calcTeacherFinishedRate($teacherId)
    {
        $currentTerm = $this->getCourseService()->getCurrentTerm();

        $teacherCurrentTermOwnedCourses = $this->getCourseMemberService()->findCoursesByTeacherId($teacherId);
        $courseIds = ArrayToolkit::column($teacherCurrentTermOwnedCourses, 'courseId');

        $coursesCurrentTermOwnedAimCounts = $this->getTeachingAimDao()->countCourseOwnedAimsByCourseIdsAndTermCode($courseIds, $currentTerm['shortCode']);
        $coursesCurrentTermOwnedAimCounts = ArrayToolkit::index($coursesCurrentTermOwnedAimCounts, 'courseId');
        $teacherCurrentTermConnectedAims = $this->getTeachingAimActivityDao()->countRelationsByCourseIdsAndTeacherIdAndTermCode($courseIds, $teacherId, $currentTerm['shortCode']);
        $teacherCurrentTermConnectedAims = ArrayToolkit::group($teacherCurrentTermConnectedAims, 'courseId');

        $result = array();
        foreach ($courseIds as $courseId) {
            if (empty($coursesCurrentTermOwnedAimCounts[$courseId]) || empty($teacherCurrentTermConnectedAims[$courseId])) {
                $result[] = array(
                    'courseId' => $courseId,
                    'rate' => 0
                );

                continue;
            }

            $result[] = array(
                'courseId' => $courseId,
                'rate' => $this->formatData(count($teacherCurrentTermConnectedAims[$courseId]) / $coursesCurrentTermOwnedAimCounts[$courseId]['count']),
            );
        }

        return $result;
    }

    public function calcCollegeFinishedRate($orgCode)
    {
        $currentTerm = $this->getCourseService()->getCurrentTerm();

        $collegeCurrentTermOwnedAims = $this->getTeachingAimDao()->findAimsByOrgCodeAndTermCode($orgCode, $currentTerm['shortCode']);
        $collegeCurrentTermConnectedAims = $this->getTeachingAimActivityDao()->findRelationsByOrgCodeAndTermCode($orgCode, $currentTerm['shortCode']);

        if (empty($collegeCurrentTermOwnedAims)) {
            return 0;
        }

        return $this->formatData(count($collegeCurrentTermConnectedAims) / count($collegeCurrentTermOwnedAims));
    }

    protected function formatData($data)
    {
        if ($data < 0) {
            return 0;
        }

        if ($data > 1) {
            return 1;
        }

        return round($data, 3);
    }

    public function deleteByActivityId($activityId)
    {
        return $this->getTeachingAimActivityDao()->deleteRelationsByActivityId($activityId);
    }

    public function processRelations($relations)
    {
        if (empty($relations)) {
            return array();
        }

        $results = array();
        foreach ($relations as $relation) {
            $results[] = $this->processRelation($relation);
        }

        return ArrayToolkit::group($results, 'type');
    }

    public function processRelation($relation)
    {
        $connectedAim = $this->getTeachingAimService()->getByAimId($relation['aimId']);
        
        return array(
            'id' => $connectedAim['id'],
            'content' => $connectedAim['content'],
            'type' => $connectedAim['type'],
        );
    }

    public function deleteByAimIds($aimIds)
    {
        if (empty($aimIds)) {
            return;
        }

        foreach ($aimIds as $aimId) {
            $this->deleteByAimId($aimId);
        }
    }

    public function deleteByAimId($aimId)
    {
        return $this->getTeachingAimActivityDao()->deleteRelationsByAimId($aimId);
    }

    protected function getCourseMemberService()
    {
        return $this->createService('CustomBundle:Course:MemberService');
    }
    
    protected function getTeachingAimService()
    {
        return $this->createService('CustomBundle:Lesson:TeachingAimService');
    }

    protected function getCourseSetService()
    {
        return $this->createService('CustomBundle:Course:CourseSetService');
    }

    protected function getCourseService()
    {
        return $this->createService('CustomBundle:Course:CourseService');
    }

    protected function getCourseLessonService()
    {
        return $this->createService('CustomBundle:Course:CourseLessonService');
    }

    protected function getTeachingAimDao()
    {
        return $this->createDao('CustomBundle:Lesson:TeachingAimDao');
    }

    protected function getTeachingAimActivityDao()
    {
        return $this->createDao('CustomBundle:Lesson:TeachingAimActivityDao');
    }
}