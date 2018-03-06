<?php

namespace CustomBundle\Biz\Course\Dao;

interface ApiCourseDao
{
    public function getByParam($xq, $kcdm, $hb, $jsdm, $zjjs, $skbj, $lx, $lbdm);

    public function findNoMakeByTerm($term);

    public function findByParam($xq, $kcdm, $hb, $skbj, $lx, $lbdm);

    public function getAlreadyMake($xq, $kcdm, $hb, $skbj, $lx, $lbdm);
}
