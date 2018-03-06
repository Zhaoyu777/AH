<?php

namespace CustomBundle\Biz\Lesson\Service\Impl;

use Biz\BaseService;
use CustomBundle\Biz\Lesson\Service\TeachingAimService;
use AppBundle\Common\ArrayToolkit;
use Codeages\Biz\Framework\Event\Event;

class TeachingAimServiceImpl extends BaseService implements TeachingAimService
{
    public function getByAimId($aimId)
    {
        return $this->getTeachingAimDao()->get($aimId);
    }

    public function getByParentIdAndLessonId($parentId, $lessonId)
    {
        return $this->getTeachingAimDao()->getAimByParentIdAndLessonId($parentId, $lessonId);
    }

    public function batchCreate($aims)
    {
        if (empty($aims)) {
            return;
        }

        foreach ($aims as &$aim) {
            if (!ArrayToolkit::requireds($aim, array('courseId', 'content', 'lessonId', 'type'))) {
                throw $this->createInvalidArgumentException('Lack of required fields');
            }

            $this->checkAim($aim);
            $aim = $this->completeAim($aim);
        }

        return $this->getTeachingAimDao()->batchCreate($aims);
    }

    protected function checkAim($aim)
    {
        if (!isset($aim['content'])) {
            if (mb_strlen($aim['content'], 'utf-8') > self::AIM_WORD_COUNT_MAX) {
                throw new \Exception("字数最多不能超过1000");
            }
        }

        if (!isset($aim['type'])) {
            if (in_array($aim['type'], array('ability', 'knowledge', 'quality'))) {
                throw new \Exception("目标类型只有ability，knowledge，quality");
            }
        }

        return;
    }

    protected function completeAim($aim)
    {
        $course = $this->getCourseService()->getCourse($aim['courseId']);
        $courseSet = $this->getCourseSetService()->getCourseSet($course['courseSetId']);
        $aim['orgCode'] = $courseSet['orgCode'];
        $aim['termCode'] = $course['termCode'];

        return $aim;
    }

    public function modifyAims($lessonId, $fields)
    {
        $this->getCourseLessonService()->tryManageCourseLesson($lessonId);

        $fields = $this->filterFields($fields);

        $this->beginTransaction();
        try {
            foreach ($fields['modifyAims']['update'] as $aim) {
                if (empty($aim)) {
                    continue;
                }
                $this->update($aim['id'], array(
                    'number' => $aim['number']
                ));
            }

            $result = $this->batchCreate($fields['modifyAims']['create']);

            $this->deleteByAimIds($fields['deleteAimIds']);

            $this->commit();
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }

        return $this->findByLessonId($lessonId);
    }

    public function update($aimId, $fields)
    {
        $fields = ArrayToolkit::parts($fields, array(
            'courseId',
            'orgCode',
            'lessonId',
            'parentId',
            'number',
            'type',
            'content',
            'termCode',
        ));

        return $this->getTeachingAimDao()->update($aimId, $fields);
    }

    public function deleteByAimIds($aimIds)
    {
        if (empty($aimIds)) {
            return;
        }

        foreach ($aimIds as $aimId) {
            $this->deleteByAimId($aimId);
        }

        $this->dispatchEvent('lesson.aims.delete', new Event(array(
            'aimIds' => $aimIds
        )));
    }

    public function deleteByAimId($aimId)
    {
        $aim = $this->getByAimId($aimId);

        if (empty($aim)) {
            return;
        }

        return $this->getTeachingAimDao()->delete($aimId);
    }

    public function deleteAimsByLessonId($lessonId)
    {
        return $this->getTeachingAimDao()->deleteAimsByLessonId($lessonId);
    }

    public function findByAimIds($aimIds)
    {
        return $this->getTeachingAimDao()->findAimsByAimIds($aimIds);
    }

    public function findByLessonId($lessonId)
    {
        return $this->getTeachingAimDao()->findAimsByLessonId($lessonId);
    }

    public function findByParentIds($aimIds)
    {
        return $this->getTeachingAimDao()->findAimsByParentIds($aimIds);
    }

    public function findUniqueCourseIds($courseIds)
    {
        return $this->getTeachingAimDao()->findUniqueCourseIds($courseIds);
    }

    public function findAllAims()
    {
        return $this->getTeachingAimDao()->findAllAims();
    }

    protected function filterFields($fields)
    {
        $lessonId = $fields['lessonId'];
        $courseId = $fields['courseId'];

        $fields = ArrayToolkit::parts($fields, array(
            'abilityAims',
            'knowledgeAims',
            'qualityAims',
        ));

        $this->checkFields($fields);

        $results = array(
            'modifyAims' => array(
                'update' => array(),
                'create' => array(),
            ),
            'deleteAimIds' => array(),
        );
        foreach ($fields as $type => $typeAims) {
            if (empty($typeAims)) {
                continue;
            }
            $number = 1;
            $deleteAimIds = ArrayToolkit::column($typeAims['deleteAims'], 'id');
            $results['deleteAimIds'] = array_merge($results['deleteAimIds'], $deleteAimIds);
            foreach ($typeAims['modifyAims'] as $modifyAim) {
                if ($modifyAim['id'] == 0) {
                    $results['modifyAims']['create'][] = array(
                        'courseId' => $courseId,
                        'lessonId' => $lessonId,
                        'content' => $modifyAim['content'],
                        'number' => $number,
                        'type' => $this->changeType($type),
                    );
                } else {
                    $results['modifyAims']['update'][] = array(
                        'id' => $modifyAim['id'],
                        'number' => $number,
                    );
                }

                $number++;
            }
        }

        return $results;
    }

    protected function checkFields($fields)
    {
        if (count($fields['abilityAims']['modifyAims']) > 20) {
            throw new \Exception("能力目标个数超过20个");
        }

        if (count($fields['knowledgeAims']['modifyAims']) > 20) {
            throw new \Exception("知识目标个数超过20个");
        }

        if (count($fields['qualityAims']['modifyAims']) > 20) {
            throw new \Exception("素质目标个数超过20个");
        }

        return;
    }

    protected function changeType($type)
    {
        $result = array(
            'abilityAims' => 'abilityAim',
            'knowledgeAims' => 'knowledgeAim',
            'qualityAims' => 'qualityAim',
        );

        return $result[$type];
    }

    public function processAims($aims)
    {
        $datas = array();

        $typeAims = ArrayToolkit::group($aims, 'type');
        
        foreach ($typeAims as $type => $aims) {
            foreach ($aims as $aim) {
                $datas[$type][] = array(
                    'id' => $aim['id'],
                    'content' => $aim['content'],
                    'number' => $aim['number']
                );
            }
        }

        return $datas;
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
}