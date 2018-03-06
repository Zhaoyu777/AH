<?php

namespace Biz\Activity\Service\Impl;

use Biz\Activity\Config\Activity;
use Biz\BaseService;
use Biz\Activity\Dao\ActivityDao;
use AppBundle\Common\ArrayToolkit;
use Biz\Course\Service\CourseService;
use Biz\Course\Service\MaterialService;
use Biz\File\Service\UploadFileService;
use Codeages\Biz\Framework\Event\Event;
use Biz\Course\Service\CourseSetService;
use Biz\Activity\Service\ActivityService;
use Biz\Activity\Service\ActivityLearnLogService;
use Biz\Activity\Listener\ActivityLearnLogListener;

class ActivityServiceImpl extends BaseService implements ActivityService
{
    public function getActivity($id, $fetchMedia = false)
    {
        $activity = $this->getActivityDao()->get($id);

        if ($fetchMedia) {
            $activity = $this->fetchMedia($activity);
        }

        return $activity;
    }

    public function getActivityByCopyIdAndCourseSetId($copyId, $courseSetId)
    {
        return $this->getActivityDao()->getByCopyIdAndCourseSetId($copyId, $courseSetId);
    }

    public function findActivities($ids, $fetchMedia = false)
    {
        $activities = $this->getActivityDao()->findByIds($ids);

        return $this->prepareActivities($fetchMedia, $activities);
    }

    public function findActivitiesByCourseIdAndType($courseId, $type, $fetchMedia = false)
    {
        $conditions = array(
            'fromCourseId' => $courseId,
            'mediaType' => $type,
        );
        $activities = $this->getActivityDao()->search($conditions, null, 0, 1000);

        return $this->prepareActivities($fetchMedia, $activities);
    }

    public function findActivitiesByCourseSetIdAndType($courseSetId, $type, $fetchMedia = false)
    {
        $conditions = array(
            'fromCourseSetId' => $courseSetId,
            'mediaType' => $type,
        );
        $activities = $this->getActivityDao()->search($conditions, null, 0, 1000);

        return $this->prepareActivities($fetchMedia, $activities);
    }

    public function search($conditions, $orderBy, $start, $limit)
    {
        return $this->getActivityDao()->search($conditions, $orderBy, $start, $limit);
    }

    public function count($conditions)
    {
        return $this->getActivityDao()->count($conditions);
    }

    public function trigger($id, $eventName, $data = array())
    {
        $activity = $this->getActivity($id);

        if (empty($activity)) {
            return;
        }

        if ($eventName == 'start') {
            $this->biz['dispatcher']->dispatch("activity.{$eventName}", new Event($activity, $data));
        }
        $this->triggerActivityLearnLogListener($activity, $eventName, $data);

        if (empty($data['events'])) {
            $events = array();
        } else {
            $events = $data['events'];
            unset($data['events']);
        }
        foreach ($events as $key => $event) {
            $data = array_merge($event, $data);
            $this->triggerActivityLearnLogListener($activity, $key, $data);
            $this->triggerExtendListener($activity, $key, $data);
        }
        if ($eventName == 'doing') {
            $this->biz['dispatcher']->dispatch("activity.{$eventName}", new Event($activity, $data));
        }
    }

    protected function triggerActivityLearnLogListener($activity, $eventName, $data)
    {
        $logListener = new ActivityLearnLogListener($this->biz);
        $logData = $this->extractLogData($eventName, $data);
        $logListener->handle($activity, $logData);
    }

    protected function triggerExtendListener($activity, $eventName, $data)
    {
        $activityListener = $this->getActivityConfig($activity['mediaType'])->getListener($eventName);
        if (null !== $activityListener) {
            $activityListener->handle($activity, $data);
        }
    }

    public function preCreateCheck($activityType, $fields)
    {
        $activity = $this->getActivityConfig($activityType);
        $activity->preCreateCheck($fields);
    }

    public function preUpdateCheck($activityId, $fields)
    {
        $activity = $this->getActivity($activityId);

        $activityInstance = $this->getActivityConfig($activity['mediaType']);
        $activityInstance->preUpdateCheck($activity, $fields);
    }

    public function createActivity($fields)
    {
        if ($this->invalidActivity($fields)) {
            throw $this->createInvalidArgumentException('activity is invalid');
        }

        $this->getCourseService()->tryManageCourse($fields['fromCourseId']);

        $activityConfig = $this->getActivityConfig($fields['mediaType']);
        $materials = $this->getMaterialsFromActivity($fields, $activityConfig);

        $media = $activityConfig->create($fields);

        if (!empty($media)) {
            $fields['mediaId'] = $media['id'];
        }

        $fields['fromUserId'] = $this->getCurrentUser()->getId();
        $fields = $this->filterFields($fields);
        $fields['createdTime'] = time();

        $activity = $this->getActivityDao()->create($fields);

        if (!empty($materials)) {
            $this->syncActivityMaterials($activity, $materials, 'create');
        }

        $listener = $activityConfig->getListener('activity.created');
        if (!empty($listener)) {
            $listener->handle($activity, array());
        }

        return $activity;
    }

    public function updateActivity($id, $fields)
    {
        $savedActivity = $this->getActivity($id);

        $this->getCourseService()->tryManageCourse($savedActivity['fromCourseId']);

        $realActivity = $this->getActivityConfig($savedActivity['mediaType']);

        $materials = $this->getMaterialsFromActivity($fields, $realActivity);
        if (!empty($materials)) {
            $this->syncActivityMaterials($savedActivity, $materials, 'update');
        }

        if (!empty($savedActivity['mediaId'])) {
            $media = $realActivity->update($savedActivity['mediaId'], $fields, $savedActivity);

            if (!empty($media)) {
                $fields['mediaId'] = $media['id'];
            }
        }

        $fields = $this->filterFields($fields);

        return $this->getActivityDao()->update($id, $fields);
    }

    public function deleteActivity($id)
    {
        $activity = $this->getActivity($id);

        try {
            $this->beginTransaction();

            $this->getCourseService()->tryManageCourse($activity['fromCourseId']);

            $this->syncActivityMaterials($activity, array(), 'delete');

            $activityConfig = $this->getActivityConfig($activity['mediaType']);
            $activityConfig->delete($activity['mediaId']);
            $this->getActivityLearnLogService()->deleteLearnLogsByActivityId($id);
            $result = $this->getActivityDao()->delete($id);
            $this->commit();

            return $result;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function isFinished($id)
    {
        $activity = $this->getActivity($id);
        $activityConfig = $this->getActivityConfig($activity['mediaType']);

        return $activityConfig->isFinished($id);
    }

    protected function syncActivityMaterials($activity, $materials, $mode = 'create')
    {
        if ($mode === 'delete') {
            $this->getMaterialService()->deleteMaterialsByLessonId($activity['id']);

            return;
        }

        if (empty($materials)) {
            return;
        }

        switch ($mode) {
            case 'create':
                foreach ($materials as $id => $material) {
                    $this->getMaterialService()->uploadMaterial($this->buildMaterial($material, $activity));
                }
                break;
            case 'update':
                $exists = $this->getMaterialService()->searchMaterials(
                    array('lessonId' => $activity['id']),
                    array('createdTime' => 'DESC'),
                    0,
                    PHP_INT_MAX
                );
                $currents = array();
                foreach ($materials as $id => $material) {
                    $currents[] = $this->buildMaterial($material, $activity);
                }

                $dropMaterials = $this->diffMaterials($exists, $currents);
                $addMaterials = $this->diffMaterials($currents, $exists);
                $updateMaterials = $this->dirtyMaterials($exists, $currents);
                foreach ($dropMaterials as $material) {
                    $this->getMaterialService()->deleteMaterial($activity['fromCourseSetId'], $material['id']);
                }
                foreach ($addMaterials as $material) {
                    $this->getMaterialService()->uploadMaterial($material);
                }
                foreach ($updateMaterials as $material) {
                    $this->getMaterialService()->updateMaterial($material['id'], $material, $material);
                }
                break;
            default:
                break;
        }
    }

    protected function buildMaterial($material, $activity)
    {
        return array(
            'fileId' => intval($material['id']),
            'courseId' => $activity['fromCourseId'],
            'courseSetId' => $activity['fromCourseSetId'],
            'lessonId' => $activity['id'],
            'title' => $material['name'],
            'description' => empty($material['summary']) ? '' : $material['summary'],
            'userId' => $this->getCurrentUser()->offsetGet('id'),
            'type' => 'course',
            'source' => $activity['mediaType'] == 'download' ? 'coursematerial' : 'courseactivity',
            'link' => empty($material['link']) ? '' : $material['link'],
            'copyId' => 0, //$fields
        );
    }

    protected function diffMaterials($arr1, $arr2)
    {
        $diffs = array();
        if (empty($arr2)) {
            return $arr1;
        }
        foreach ($arr1 as $value1) {
            $contained = false;
            foreach ($arr2 as $value2) {
                if ($value1['fileId'] == 0) {
                    $contained = $value1['link'] == $value2['link'];
                } else {
                    $contained = $value1['fileId'] == $value2['fileId'];
                }
                if ($contained) {
                    break;
                }
            }
            if (!$contained) {
                $diffs[] = $value1;
            }
        }

        return $diffs;
    }

    protected function dirtyMaterials($exists, $currents)
    {
        $diffs = array();
        if (empty($arr2)) {
            return $diffs;
        }
        foreach ($exists as $exist) {
            foreach ($currents as $current) {
                //如果fileId存在则匹配fileId，否则匹配link
                if (($exist['fileId'] != 0 && $exist['fileId'] == $current['fileId'])
                    || ($exist['fileId'] == 0 && $exist['link'] == $current['link'])
                ) {
                    $current['id'] = $exist['id'];
                    if (empty($current['description'])) {
                        $current['description'] = $exist['description'];
                    }
                    $diffs[] = $current;
                    break;
                }
            }
        }

        return $diffs;
    }

    protected function filterFields($fields)
    {
        $fields = ArrayToolkit::parts(
            $fields,
            array(
                'title',
                'remark',
                'mediaId',
                'mediaType',
                'content',
                'length',
                'fromCourseId',
                'fromCourseSetId',
                'fromUserId',
                'startTime',
                'endTime',
            )
        );

        if (!empty($fields['startTime']) && !empty($fields['length']) && $fields['mediaType'] != 'testpaper') {
            $fields['endTime'] = $fields['startTime'] + $fields['length'] * 60;
        }

        if (empty($fields['mediaType'])) {
            unset($fields['mediaType']);
        }

        return $fields;
    }

    protected function invalidActivity($activity)
    {
        if (!ArrayToolkit::requireds(
            $activity,
            array(
                'title',
                'mediaType',
                'fromCourseId',
                'fromCourseSetId',
            )
        )
        ) {
            return true;
        }
        $activity = $this->getActivityConfig($activity['mediaType']);
        if (!is_object($activity)) {
            return true;
        }

        return false;
    }

    /**
     * @param  $fields
     *
     * @return array 多维数组
     */
    public function getMaterialsFromActivity($fields, $activityConfig)
    {
        if (!empty($fields['materials'])) {
            return json_decode($fields['materials'], true);
        }

        if (!empty($fields['media'])) {
            $media = json_decode($fields['media'], true);
            if (!empty($media['id'])) {
                return array($media);
            }
        }
    }

    /**
     * @param  $activity
     *
     * @return mixed
     */
    public function fetchMedia($activity)
    {
        if (!empty($activity['mediaId'])) {
            $activityConfig = $this->getActivityConfig($activity['mediaType']);
            $media = $activityConfig->get($activity['mediaId']);
            $activity['ext'] = $media;

            return $activity;
        }

        return $activity;
    }

    public function fetchMedias($mediaType, $activities)
    {
        $activityConfig = $this->getActivityConfig($mediaType);

        $mediaIds = ArrayToolkit::column($activities, 'mediaId');
        $medias = $activityConfig->find($mediaIds);

        $medias = ArrayToolkit::index($medias, 'id');

        array_walk(
            $activities,
            function (&$activity) use ($medias) {
                //part of the activity have no extension table
                $activity['ext'] = empty($medias[$activity['mediaId']]) ? array() : $medias[$activity['mediaId']];
            }
        );

        return $activities;
    }

    public function findActivitySupportVideoTryLook($courseIds)
    {
        $activities = $this->getActivityDao()->findSelfVideoActivityByCourseIds($courseIds);
        $cloudFiles = $this->findCloudFilesByMediaIds($activities);
        $activities = array_filter($activities, function ($activity) use ($cloudFiles) {
            return !empty($cloudFiles[$activity['fileId']]);
        });

        return $activities;
    }

    public function getActivityConfig($type)
    {
        return $this->biz["activity_type.{$type}"];
    }

    /**
     * @return MaterialService
     */
    protected function getMaterialService()
    {
        return $this->createService('Course:MaterialService');
    }

    /**
     * @return ActivityDao
     */
    protected function getActivityDao()
    {
        return $this->createDao('Activity:ActivityDao');
    }

    /**
     * @return ActivityLearnLogService
     */
    protected function getActivityLearnLogService()
    {
        return $this->createService('Activity:ActivityLearnLogService');
    }

    /**
     * @return CourseService
     */
    protected function getCourseService()
    {
        return $this->createService('Course:CourseService');
    }

    /**
     * @return CourseSetService
     */
    protected function getCourseSetService()
    {
        return $this->createService('Course:CourseSetService');
    }

    /**
     * @return UploadFileService
     */
    protected function getUploadFileService()
    {
        return $this->createService('File:UploadFileService');
    }

    /**
     * @param $activity
     * @param $eventName
     * @param $data
     *
     * @return mixed
     */
    protected function extractLogData($eventName, $data)
    {
        unset($data['task']);
        $logData = $data;
        $logData['event'] = $eventName;

        return $logData;
    }

    /**
     * @param $fetchMedia
     * @param $activities
     * @param $sortedActivities
     *
     * @return mixed
     */
    protected function prepareActivities($fetchMedia, $activities)
    {
        if (empty($activities)) {
            return $activities;
        }
        $activityGroups = ArrayToolkit::group($activities, 'mediaType');
        if ($fetchMedia) {
            foreach ($activityGroups as $mediaType => $activityGroup) {
                $activityGroups[$mediaType] = $this->fetchMedias($mediaType, $activityGroup);
            }
        }

        $fullActivities = array();
        foreach ($activityGroups as $activityGroup) {
            $fullActivities = array_merge($fullActivities, array_values($activityGroup));
        }

        $activityIds = ArrayToolkit::column($activities, 'id');

        foreach ($fullActivities as $activity) {
            $key = array_search($activity['id'], $activityIds);
            $sortedActivities[$key] = $activity;
        }
        ksort($sortedActivities);

        return $sortedActivities;
    }

    /**
     * @param $activities
     *
     * @return array
     */
    protected function findCloudFilesByMediaIds($activities)
    {
        $fileIds = ArrayToolkit::column($activities, 'fileId');
        $files = $this->getUploadFileService()->findFilesByIds($fileIds);
        $cloudFiles = array_filter($files, function ($file) {
            return $file['storage'] === 'cloud';
        });
        $cloudFiles = ArrayToolkit::index($cloudFiles, 'id');

        return $cloudFiles;
    }
}
