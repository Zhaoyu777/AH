<?php 

namespace CustomBundle\Biz\Lesson\Service;

interface TeachingAimService 
{
    const AIMS_COUNT_MAX = 20;

    const AIM_WORD_COUNT_MAX = 1000;

    public function getByAimId($aimId);

    public function getByParentIdAndLessonId($parentId, $lessonId);

    public function batchCreate($aims);

    public function update($aimId, $fields);

    public function modifyAims($lessonId, $fields);

    public function processAims($aims);

    public function deleteAimsByLessonId($lessonId);

    public function deleteByAimIds($aimIds);

    public function deleteByAimId($aimId);

    public function findByAimIds($aimIds);

    public function findByLessonId($lessonId);

    public function findUniqueCourseIds($courseIds);

    public function findByParentIds($aimIds);
}