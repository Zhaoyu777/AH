<?php
namespace CustomBundle\Biz\Course\Dao;

interface CzieApiCourseSetDao
{
    public function getByKcdm($code);

    public function getByCourseSetId($courseSetId);
}
