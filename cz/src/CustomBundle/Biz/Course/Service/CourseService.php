<?php

namespace CustomBundle\Biz\Course\Service;

use Biz\Course\Service\CourseService as BaseCourseService;

interface CourseService extends BaseCourseService
{
    public function createCourse($course);

    public function findNotClosedCourseCountsBySetIdsAndTeacherId($courseSetIds, $teacherId);

    public function findStudentCountsByCourseIds($courseIds);

    public function findNotClosedCoursesByTeacherId($teacherId);

    public function findNotClosedCoursesByTeacherIdAndTermCode($teacherId, $termCode);

    public function findLecturersByCourseId($courseId);

    public function findNormalCoursesByIds($ids);

    public function sortImportCourses($ids);

    public function findChapterByCourseIdAndLessonId($courseId, $lessonId);

    public function isAnyLessonStart($courseId);
}
