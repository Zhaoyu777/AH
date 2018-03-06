<?php

namespace CustomBundle\Biz\Activity\Type;

use AppBundle\Common\ArrayToolkit;
use Biz\Activity\Config\Activity;

class Interval extends Activity
{
    protected function registerListeners()
    {
        return array('watching' => 'Biz\Activity\Listener\VideoActivityWatchListener');
    }

    public function create($fields)
    {
        if (empty($fields['media'])) {
            throw $this->createInvalidArgumentException('参数不正确');
        }
        $media = json_decode($fields['media'], true);

        if (empty($media['id'])) {
            throw $this->createInvalidArgumentException('参数不正确');
        }

        $interval['mediaId'] = $media['id'];
        $interval['mediaSource'] = $fields['ext']['mediaSource'];
        $interval = $this->getIntervalActivityDao()->create($interval);

        return $interval;
    }

    public function copy($activity, $config = array())
    {
        $interval = $this->getIntervalActivityDao()->get($activity['mediaId']);
        $newInterval = array(
            'mediaSource' => $interval['mediaSource'],
            'mediaId' => $interval['mediaId'],
            'mediaUri' => $interval['mediaUri'],
        );

        return $this->getIntervalActivityDao()->create($newInterval);
    }

    public function sync($sourceActivity, $activity)
    {
        $sourceInterval = $this->getIntervalActivityDao()->get($sourceActivity['mediaId']);
        $interval = $this->getIntervalActivityDao()->get($activity['mediaId']);
        $interval['mediaSource'] = $sourceInterval['mediaSource'];
        $interval['mediaId'] = $sourceInterval['mediaId'];
        $interval['mediaUri'] = $sourceInterval['mediaUri'];
        $interval['finishType'] = $sourceInterval['finishType'];
        $interval['finishDetail'] = $sourceInterval['finishDetail'];

        return $this->getIntervalActivityDao()->update($interval['id'], $interval);
    }

    public function update($activityId, &$fields, $activity)
    {
        $interval = $fields['ext'];
        if ($interval['finishType'] == 'time') {
            if (empty($interval['finishDetail'])) {
                throw $this->createAccessDeniedException('finish time can not be emtpy');
            }
        }
        $intervalActivity = $this->getIntervalActivityDao()->get($fields['mediaId']);
        if (empty($intervalActivity)) {
            throw new \Exception('教学活动不存在');
        }

        if (isset($interval['finishType'])) {
            unset($interval['finishType']);
        }

        $intervalActivity = $this->getIntervalActivityDao()->update($fields['mediaId'], $interval);

        return $intervalActivity;
    }

    public function isFinished($activityId)
    {
        $activity = $this->getActivityService()->getActivity($activityId);
        $interval = $this->getIntervalActivityDao()->get($activity['mediaId']);
        if ($interval['finishType'] == 'time') {
            $result = $this->getActivityLearnLogService()->sumMyLearnedTimeByActivityId($activityId);
            $result /= 60;

            return !empty($result) && $result >= $interval['finishDetail'];
        }

        if ($interval['finishType'] == 'end') {
            $logs = $this->getActivityLearnLogService()->findMyLearnLogsByActivityIdAndEvent($activityId, 'finish');

            return !empty($logs);
        }

        return false;
    }

    public function get($id)
    {
        $intervalActivity = $this->getIntervalActivityDao()->get($id);
        // Todo 临时容错处理
        try {
            $intervalActivity['file'] = $this->getUploadFileService()->getFullFile($intervalActivity['mediaId']);
        } catch (CloudAPIIOException $e) {
            return array();
        }

        return $intervalActivity;
    }

    public function find($ids)
    {
        $intervalActivities = $this->getIntervalActivityDao()->findByIds($ids);
        $mediaIds = ArrayToolkit::column($intervalActivities, 'mediaId');
        try {
            $files = $this->getUploadFileService()->findFilesByIds(
                $mediaIds,
                $showCloud = 1
            );
        } catch (CloudAPIIOException $e) {
            $files = array();
        }

        if (empty($files)) {
            return $intervalActivities;
        }
        $files = ArrayToolkit::index($files, 'id');
        array_walk(
            $intervalActivities,
            function (&$intervalActivity) use ($files) {
                $intervalActivity['file'] = isset($files[$intervalActivity['mediaId']]) ? $files[$intervalActivity['mediaId']] : null;
            }
        );

        return $intervalActivities;
    }

    public function delete($id)
    {
        return $this->getIntervalActivityDao()->delete($id);
    }

    protected function getIntervalActivityDao()
    {
        return $this->getBiz()->dao('CustomBundle:Activity:IntervalActivityDao');
    }

    protected function getUploadFileService()
    {
        return $this->getBiz()->service('File:UploadFileService');
    }

    protected function getActivityLearnLogService()
    {
        return $this->getBiz()->service('Activity:ActivityLearnLogService');
    }

    protected function getActivityService()
    {
        return $this->getBiz()->service('Activity:ActivityService');
    }
}
