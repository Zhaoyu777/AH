<?php

namespace CustomBundle\Biz\Course\Service;

interface CourseLessonService
{
    public function createCourseLesson($lesson);

    public function batchCreateCourseLessons($courseId, $count);

    public function updateCourseLesson($id, $fields);

    public function startCourseLesson($id);

    public function endCourseLesson($id);

    public function cancelCourseLesson($id);

    public function getCourseLesson($id);

    public function getCurrenTeachCourseLesson($courseId);

    public function getLessonTaskByTaskId($taskId);

    public function findCourseLessonsByCourseId($courseId);

    public function findCourseLessonsByIds($ids);

    public function findCourseLessonCountByCourseIds($courseIds);

    public function createChapter($chapter);

    public function deleteChapter($chapterId);

    public function updateChapter($chapterId, $fields);

    public function getChapter($chapterId);

    public function findChaptersByLessonId($lessonId);

    public function findLessonTasksByLessonId($lessonId);

    public function findLessonTasksByLessonIdAndStage($lessonId, $stage);

    public function countLessonTask($conditions);

    public function countLessonUnfinishedTask($conditions);

    public function countLessonTasksByLessonIds($lessonIds);

    public function createLessonTask($task);

    public function findTeachingLessons();

    public function endLessons($lessonIds);

    public function getStudyLessonByCourseId($courseId);

    public function findCourseLessonsByCourseIdAndStatus($courseId, $status);

    public function getCourseChapter($courseChapterId);

    public function findChaptersByLessonIdAndTpye($lessonId, $type);

    public function findLessonsByFromCourseIdAndtoCourseId($fromCourseId, $toCourseId);

    public function countStudentsFinishOutTasksByCourseId($courseId);

    public function findTeachedLessonTasksByCourseId($courseId);

    public function findLessonTasksByTaskIds($taskIds);

    public function countSchoolTeachersPrepareLessonsByTermCode($termCode);

    public function countScholeTeacherReportsByTermCode($termCode);
}
