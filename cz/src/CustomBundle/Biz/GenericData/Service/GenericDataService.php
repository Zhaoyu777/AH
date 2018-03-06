<?php

namespace CustomBundle\Biz\GenericData\Service;

interface GenericDataService
{
    public function createData($data);

    public function destroyData($type);

    public function getDataByType($type);
}
