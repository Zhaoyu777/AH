<?php

namespace CustomBundle\Biz\Activity\Service\Impl;

use Biz\BaseService;
use AppBundle\Common\ArrayToolkit;
use Codeages\Biz\Framework\Event\Event;
use CustomBundle\Biz\Activity\Service\RollcallResultService;

class RollcallResultServiceImpl extends BaseService implements RollcallResultService
{
    public function createResult($result)
    {
        if (!ArrayToolkit::requireds($result, array('activityId', 'courseId', 'courseTaskId', 'userId'))) {
            throw $this->createInvalidArgumentException('Lack of required fields');
        }

        $selectUser = empty($result['selectUser']) ? array() : $result['selectUser'];
        $students = empty($result['students']) ? array() : $result['students'];
        $result = ArrayToolkit::parts($result, array(
            'activityId',
            'courseId',
            'courseTaskId',
            'userId',
        ));
        $user = $this->getCurrentUser();
        $result['opUserId'] = $user['id'];

        $this->beginTransaction();
        try {
            $created = $this->getResultDao()->create($result);

            $this->commit();

            $selectUser['resultId'] = $created['id'];
            $this->dispatchEvent('push.rollcall.result.create', new Event(array(
                'selectUser' => $selectUser,
                'result' => $created,
                'students' => $students,
            )));

            return $created;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function deleteResult($resultId)
    {
        $result = $this->getResult($resultId);

        if (empty($result)) {
            return ;
        }

        $this->getResultDao()->delete($resultId);

        $this->dispatchEvent('rollcall.result.delete', new Event($result));
    }

    public function deleteResultsByTaskIds($taskIds)
    {
        $results = $this->getResultDao()->findByTaskIds($taskIds);

        foreach ($results as $key => $result) {
            $this->deleteResult($result['id']);
        }
    }

    public function remarkResult($id, $fields)
    {
        $result = $this->getResult($id);

        if (empty($result)) {
            throw $this->createNotFoundException("result#{$id} Not Found");
        }

        $fields = ArrayToolkit::parts($fields, array('score', 'remark'));
        if (!empty($fields['remark'])) {
            $fields['remark'] = array_filter($fields['remark']);
        }

        $created = $this->getResultDao()->update($id, $fields);
        $this->dispatchEvent('rollcall.remark', new Event($created));
        $this->dispatchEvent('task.result.remark', new Event($created));

        return $created;
    }

    public function getResult($id)
    {
        return $this->getResultDao()->get($id);
    }

    public function findResults($ids)
    {
        return $this->getResultDao()->findByIds($ids);
    }

    public function getResultByTaskIdAndUserId($taskId, $userId)
    {
        return $this->getResultDao()->getByTaskIdAndUserId($taskId, $userId);
    }

    public function findResultsByTaskId($taskId)
    {
        return $this->getResultDao()->findByTaskId($taskId);
    }

    protected function getResultDao()
    {
        return $this->createDao('CustomBundle:Activity:RollcallResultDao');
    }
}
