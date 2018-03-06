<?php

namespace CustomBundle\Biz\Course\Service\Impl;

use AppBundle\Common\ArrayToolkit;
use CustomBundle\Biz\Course\Service\CourseSetService;
use Biz\Course\Service\Impl\CourseSetServiceImpl as BaseCourseSetServiceImpl;

class CourseSetServiceImpl extends BaseCourseSetServiceImpl implements CourseSetService
{
    public function tryManageCourseSet($id)
    {
        $user = $this->getCurrentUser();
        if (!$user->isLogin()) {
            throw $this->createAccessDeniedException('user not login');
        }

        $courseSet = $this->getCourseSetDao()->get($id);

        if (empty($courseSet)) {
            throw $this->createNotFoundException("CourseSet#{$id} Not Found");
        }

        if ($courseSet['status'] == 'delete') {
            throw $this->createNotFoundException("该课程已删除");
        }

        if ($courseSet['parentId'] > 0) {
            $classroomCourse = $this->getClassroomService()->getClassroomCourseByCourseSetId($id);
            if (!empty($classroomCourse)) {
                $classroom = $this->getClassroomService()->getClassroom($classroomCourse['classroomId']);
                if (!empty($classroom) && $classroom['headTeacherId'] == $user['id']) {
                    //班主任有权管理班级下所有课程
                    return $courseSet;
                }
            }
        }
        if (!$this->hasCourseSetManageRole($id)) {
            throw $this->createAccessDeniedException('can not access');
        }

        return $courseSet;
    }

    public function createInstantCourseSet($courseSet)
    {
        if (!ArrayToolkit::requireds($courseSet, array('title'))) {
            throw $this->createInvalidArgumentException('Lack of required fields');
        }

        if (!in_array($courseSet['type'], array('instant', 'normal', 'live', 'liveOpen', 'open'))) {
            throw $this->createInvalidArgumentException('Invalid Param: type');
        }

        $data = $courseSet;

        $courseSet = ArrayToolkit::parts(
            $courseSet,
            array(
                'title',
                'userId',
                'courseNo',
                'type'
            )
        );

        $courseSet['status'] = 'published';

        $courseSet['creator'] = $this->getCurrentUser()->getId();

        return $this->getCourseSetDao()->create($courseSet);
    }

    public function setDefaultCourseId($courseSetId, $courseId)
    {
        $courseSet = $this->getCourseSet($courseSetId);

        if (!empty($courseSet)) {
            $courseSet = $this->getCourseSetDao()->update($courseSetId, array('defaultCourseId' => $courseId));
        }

        return $courseSet;
    }

    public function getByUserIdAndCourseNo($userId, $courseNo)
    {
        return $this->getCourseSetDao()->getByUserIdAndCourseNo($userId, $courseNo);
    }

    public function deleteInstantCourseSet($courseSetId)
    {
        $CourseSet = $this->getCourseSet($courseSetId);

        if (empty($CourseSet)) {
            throw $this->createNotFoundException('Course not found');
        }

        $apiCourseSet = $this->getApiCourseSetByCourseSetId($courseSetId);

        if (!empty($apiCourseSet)) {
            throw $this->createAccessDeniedException('校级课程不允许删除！！');
        }

        $fields = array(
            'status' => 'delete'
        );

        return $this->getCourseSetDao()->update($courseSetId, $fields);
    }

    public function findApiCourseSetsByCourseSetIds($courseSetIds)
    {
        return $this->getApiCourseSetDao()->findByCourseSetIds($courseSetIds);
    }

    public function findApiCourseSetIdsByCourseSetIds($courseSetIds)
    {
        $apiCourseSets = $this->findApiCourseSetsByCourseSetIds($courseSetIds);

        return ArrayToolkit::column($apiCourseSets, 'courseSetId');
    }

    public function getApiCourseSetByCourseSetId($courseSetId)
    {
        return $this->getApiCourseSetDao()->getByCourseSetId($courseSetId);
    }

    public function countCourseSets(array $conditions)
    {
        $conditions = $this->prepareConditions($conditions);

        return $this->getCourseSetDao()->count($conditions);
    }

    protected function prepareConditions($conditions)
    {
        $conditions = array_filter($conditions, function ($value) {
            if (is_numeric($value)) {
                return true;
            }

            return !empty($value);
        });

        if (!empty($conditions['creatorName'])) {
            $user                  = $this->getUserService()->getUserByNickname($conditions['creatorName']);
            $conditions['creator'] = $user ? $user['id'] : -1;
        }

        if (empty($conditions['type']) && empty($conditions['allowAll'])) {
            $conditions['excludeType'] = 'instant';
        }

        return $conditions;
    }

    public function countCourseSetsWithCourseNo($conditions)
    {
        return $this->getCourseSetDao()->countCourseSetsWithCourseNo($conditions);
    }

    public function searchCourseSetsWithCourseNo(array $conditions, $orderBys, $start, $limit)
    {
        return $this->getCourseSetDao()->searchWithCourseNo($conditions, $orderBys, $start, $limit);
    }

    public function updateCourseSetTeacherId($id, $teacherId)
    {
        $this->tryManageCourseSet($id);

        return $this->getCourseSetDao()->update($id, array('userId' => $teacherId));
    }

    protected function getCourseService()
    {
        return $this->biz->service('CustomBundle:Course:CourseService');
    }

    protected function getCourseLessonService()
    {
        return $this->biz->service('CustomBundle:Course:CourseLessonService');
    }

    protected function getApiCourseSetDao()
    {
        return $this->createDao('CustomBundle:Course:CzieApiCourseSetDao');
    }

    protected function getCourseSetDao()
    {
        return $this->createDao('CustomBundle:Course:CourseSetDao');
    }
}
