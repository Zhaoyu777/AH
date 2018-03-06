<?php

namespace CustomBundle\Biz\Course\Copy;

abstract class AbstractCopy
{
    abstract public function copy($fromId, $toId);
}
