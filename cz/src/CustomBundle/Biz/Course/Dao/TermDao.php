<?php

namespace CustomBundle\Biz\Course\Dao;

interface TermDao
{
    public function getByShortCode($code);

    public function getCurrentTerm();

    public function reset();
}
