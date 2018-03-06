<?php

namespace CustomBundle\Biz\Report\Service;

interface StudentLessonReportService
{
    public function create($field);

    public function createAll($fields);

    public function createLessonReport($lessonId);

    public function getbeatRateByLessonIdAndUserId($lessonId, $userId);

    public function getStudentReport($files);

    public function findReportBylessonId($lessonId);

    public function getReportBylessonIdAndUserId($lessonId, $userId);

    public function findReportBycourseId($courseId);

    public function findReportBycourseIdAndUserId($courseId, $userId);

}