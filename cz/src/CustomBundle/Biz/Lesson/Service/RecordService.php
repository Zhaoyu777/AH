<?php 

namespace CustomBundle\Biz\Lesson\Service;

interface RecordService 
{
    public function create($fields);

    public function update($recordId, $fields);

    public function delete($recordId);

    public function getByRecordId($recordId);

    public function getByLessonId($lessonId);
}