<?php

namespace CustomBundle\Biz\Course\Service;

interface PrepareCourseLogService
{
    public function createLog($courseId);

    public function getLogByCourseId($courseId);

    public function countCurrentTermLog();

    public function countLogByTermCode($termCode);
}
