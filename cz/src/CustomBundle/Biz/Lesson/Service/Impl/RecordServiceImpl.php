<?php

namespace CustomBundle\Biz\Lesson\Service\Impl;

use Biz\BaseService;
use CustomBundle\Biz\Lesson\Service\RecordService;
use AppBundle\Common\ArrayToolkit;
use Codeages\Biz\Framework\Event\Event;

class RecordServiceImpl extends BaseService implements RecordService
{
    public function create($fields)
    {
        if (!ArrayToolkit::requireds($fields, array('courseSetId', 'courseId', 'lessonId', 'teacherId'))) {
            throw $this->createInvalidArgumentException('Lack of required fields');
        }

        if (!$this->canRecord($fields['courseSetId'], $fields['courseId'])) {
            throw $this->createAccessDeniedException('has no access');
        }

        $fields = ArrayToolkit::parts($fields, array(
            'courseSetId',
            'courseId',
            'lessonId',
            'teacherId',
            'taskId'
        ));

        $record = $this->getRecordDao()->create($fields);

        $this->dispatchEvent('lesson.record.create', new Event($record));

        return $record;
    }

    public function update($recordId, $fields)
    {
        $record = $this->getByRecordId($recordId);

        if (empty($record)) {
            throw $this->createNotFoundException('record not found');
        }

        if (!$this->canRecord($record['courseSetId'], $record['courseId'])) {
            throw $this->createAccessDeniedException('has no access');
        }

        $fields = ArrayToolkit::parts($fields, array(
            'courseSetId',
            'courseId',
            'lessonId',
            'teacherId',
            'taskId'
        ));

        $record = $this->getRecordDao()->update($recordId, $fields);

        $this->dispatchEvent('lesson.record.update', new Event($record));

        return $record;
    }

    public function delete($recordId)
    {
        $record = $this->getByRecordId($recordId);

        if (empty($record)) {
            return;
        }

        if (!$this->canRecord($record['courseSetId'], $record['courseId'])) {
            return;
        }

        return $this->getRecordDao()->delete($recordId);
    }

    public function deleteRecordsByLessonId($lessonId)
    {
        return $this->getRecordDao()->deleteByLesonId($lessonId);
    }

    public function getByRecordId($recordId)
    {
        return $this->getRecordDao()->get($recordId);
    }

    protected function canRecord($courseSetId, $courseId)
    {
        $user = $this->getCurrentUser();
        if (!$this->getCourseMemberService()->isCourseTeacher($courseId, $user['id'])) {
            return false;
        }

        if (!$this->isCourseSetInstant($courseSetId)) {
            return false;
        }

        return true;
    }

    protected function isCourseSetInstant($courseSetId)
    {
        $courseSet = $this->getCourseSetService()->getCourseSet($courseSetId);

        if ($courseSet['type'] == 'instant') {
            return true;
        }

        return false;
    }

    public function getByCourseId($courseId)
    {
        $record = $this->getRecordDao()->getByCourseId($courseId);

        return $record;
    }

    public function changeLessonRecordByLessonId($lessonId, $taskId)
    {
        $user = $this->getCurrentUser();
        $record = $this->getByLessonId($lessonId);

        if (empty($record)) {
            return ;
        }

        return $this->update($record['id'], array(
            'teacherId' => $user['id'],
            'taskId' => $taskId,
        ));
    }

    public function getByLessonId($lessonId)
    {
        return $this->getRecordDao()->getRecordByLessonId($lessonId);
    }

    protected function getRecordDao()
    {
        return $this->createDao('CustomBundle:Lesson:RecordDao');
    }

    protected function getCourseSetService()
    {
        return $this->createService('Course:CourseSetService');
    }

    protected function getCourseMemberService()
    {
        return $this->createService('Course:MemberService');
    }
}
