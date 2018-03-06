<?php

namespace CustomBundle\Biz\File\Service\Impl;

use AppBundle\Common\ArrayToolkit;
use CustomBundle\Biz\File\Service\UploadFileService;
use Biz\File\Service\Impl\UploadFileServiceImpl as BaseUploadFileServiceImpl;

class UploadFileServiceImpl extends BaseUploadFileServiceImpl implements UploadFileService
{
    public function finishedUpload($params)
    {
        $this->beginTransaction();
        try {
            $setting = $this->getSettingService()->get('storage');
            $params['storage'] = empty($setting['upload_mode']) ? 'local' : $setting['upload_mode'];

            if (empty($params['length'])) {
                $params['length'] = 0;
            }

            $implementor = $this->getFileImplementor($params['storage']);

            $fields = array(
                'status' => 'ok',
                'convertStatus' => 'none',
                'length' => $params['length'],
                'fileSize' => $params['size'],
            );

            $file = $this->getUploadFileInitDao()->update($params['id'], array('status' => 'ok'));

            $file = array_merge($file, $fields);

            $term = $this->getCourseService()->getCurrentTerm();
            $file['termCode'] = empty($term) ? null : $term['shortCode'];

            $file = $this->getUploadFileDao()->create($file);

            $result = $implementor->finishedUpload($file, $params);

            if (empty($result) || !$result['success']) {
                throw $this->createServiceException('uploadFile失败，完成上传失败！');
            }

            $file = $this->getUploadFileDao()->update($file['id'], array(
                'length' => isset($result['length']) ? $result['length'] : 0,
            ));

            $this->getLogService()->info('upload_file', 'create', "新增文件(#{$file['id']})", $file);

            $this->getLogger()->info("finishedUpload 添加文件：#{$file['id']}");

            if ($file['targetType'] == 'headLeader') {
                $headLeaders = $this->getUploadFileDao()->findHeadLeaderFiles();

                foreach ($headLeaders as $headLeader) {
                    if ($headLeader['id'] != $file['id']) {
                        $this->deleteFile($headLeader['id']);
                    }
                }
            }

            $this->dispatchEvent('upload.file.finish', array('file' => $file));

            $this->commit();

            return $file;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function searchFileCount($conditions)
    {
        $conditions = $this->_prepareSearchConditions($conditions);

        if ($this->hasProcessStatusCondition($conditions)) {
            return $this->searchFileCountFromCloud($conditions);
        } else {
            return $this->getUploadFileDao()->count($conditions);
        }
    }

    public function searchFileCountFromCloud($conditions)
    {
        $files = $this->getUploadFileDao()->search($conditions, array('createdTime' => 'DESC'), 0, PHP_INT_MAX);
        $globalIds = ArrayToolkit::column($files, 'globalId');

        if (empty($globalIds)) {
            return 0;
        }

        $cloudFileConditions = array(
            'processStatus' => $conditions['processStatus'],
        );
        $globalArray = array_chunk($globalIds, 20);
        $count = 0;

        foreach ($globalArray as $key => $globals) {
            $cloudFileConditions['nos'] = implode(',', $globals);

            $cloudFiles = $this->getFileImplementor('cloud')->search($cloudFileConditions);
            $count += $cloudFiles['count'];
        }

        return $count;
    }

    public function countFileByTimeRangeAndOrgCodeGroupType($startTime, $endTime, $orgCode)
    {
        return $this->getUploadFileDao()->countByTimeRangeAndOrgCodeGroupType($startTime, $endTime, $orgCode);
    }

    protected function getCourseService()
    {
        return $this->createService('CustomBundle:Course:CourseService');
    }

    public function countSuccessfulFiles()
    {
        return $this->getUploadFileDao()->count(array('status' => 'ok'));
    }

    public function addActivityPracticeFile($practiceContentId)
    {
        $content = $this->getPracticeResultService()->getContent($practiceContentId);
        $result = $this->getPracticeResultService()->getResult($content['resultId']);
        if ($result['isCollected']) {
            return;
        }
        $this->beginTransaction();
        try {
            $parsed = $this->getFileService()->parseFileUri($content["uri"]);
            $filePath = "{$this->biz['topxia.upload.public_directory']}/".$parsed['path'];

            $initParams = $this->getPracticeFileImplementor()->initUpload('activity-practice', $result['courseTaskId'], $filePath);
            $initFile = $this->getUploadFileInitDao()->create($initParams);
            $file = $this->getPracticeFileImplementor()->addFile('activity-practice', $result['courseTaskId'], $filePath);
            $file['id'] = $initFile['id'];
            $file = $this->getUploadFileDao()->create($file);

            $this->getLogService()->info('upload_file', 'create', "添加文件(#{$file['id']})", $file);
            $this->getLogger()->info("addFile 添加文件：#{$file['id']}");

            $task = $this->getTaskService()->getTask($result['courseTaskId']);
            $this->getMaterialService()->uploadMaterial(array(
                'fileId' => $file['id'],
                'courseId' => $task['courseId'],
                'courseSetId' => $task['fromCourseSetId'],
                'source' => 'activitypractice',
            ));

            $this->getPracticeResultService()->updateResult($result['id'], array('isCollected' => 1));
            $this->commit();
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    protected function getPracticeFileImplementor()
    {
        return $this->biz->service('CustomBundle:File:PracticeFileImplementor');
    }

    protected function getFileService()
    {
        return $this->createService('Content:FileService');
    }

    protected function getTaskService()
    {
        return $this->createService('CustomBundle:Task:TaskService');
    }

    protected function getPracticeResultService()
    {
        return $this->createService('CustomBundle:Practice:PracticeResultService');
    }

    protected function getMaterialService()
    {
        return $this->createService('CustomBundle:Course:MaterialService');
    }

    protected function getUploadFileDao()
    {
        return $this->createDao('CustomBundle:File:UploadFileDao');
    }
}
