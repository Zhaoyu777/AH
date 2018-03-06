<?php

namespace CustomBundle\Biz\Course\Service;

interface CourseImportService
{
    public function importCourse($fromCourseId, $toCourseId);

    public function importCourseLesson($fromLessonId, $toLessonId);
}
