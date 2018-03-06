<?php

namespace CustomBundle\Biz\GenericData\Service\Impl;

use Biz\BaseService;
use AppBundle\Common\ArrayToolkit;
use CustomBundle\Biz\GenericData\Service\GenericDataService;

class GenericDataServiceImpl extends BaseService implements GenericDataService
{
    public function createData($data)
    {
        if (!ArrayToolkit::requireds($data, array('type', 'data'))) {
            throw $this->createInvalidArgumentException('Lack of required fields');
        }

        $data = ArrayToolkit::parts($data, array(
            'type',
            'data',
            'times',
            'expiredTime',
        ));
        $user = $this->getCurrentUser();
        $data['userId'] = $user['id'];

        if (!empty($data['times'])) {
            $data['remainedTimes'] = $data['times'];
        }

        return $this->getGenericDataDao()->create($data);
    }

    public function destroyData($type)
    {
        $data = $this->getDataByType($type);

        if (empty($data)) {
            return ;
        }

        $this->getGenericDataDao()->delete($data['id']);
    }

    public function getDataByType($type)
    {
        $data = $this->getGenericDataDao()->getDataByType($type);

        if (($data['expiredTime'] > 0) && ($data['expiredTime'] < time())) {
            $this->getGenericDataDao()->delete($data['id']);

            return null;
        }

        return $data;
    }

    protected function getGenericDataDao()
    {
        return $this->createDao('CustomBundle:GenericData:GenericDataDao');
    }
}
