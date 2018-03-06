<?php

namespace Biz\File\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

// TODO refactor.
interface UploadFileService
{
    public function getFile($id);

    public function getFileByGlobalId($globalId);

    public function getFullFile($id);

    public function getUploadFileInit($id);

    public function findFilesByTargetTypeAndTargetIds($targetType, $targetIds);

    public function update($fileId, $fields);

    public function getDownloadMetas($id, $ssl = false);

    public function getUploadAuth($params);

    public function initUpload($params);

    public function finishedUpload($params);

    public function moveFile($targetType, $targetId, $originalFile = null, $data = array());

    public function setFileProcessed($params);

    public function deleteByGlobalId($globalId);

    public function reconvertFile($id, $options = array());

    public function reconvertOldFile($id, $convertCallback, $pipeline);

    public function collectFile($userId, $fileId);

    public function findCollectionsByUserIdAndFileIds($fileIds, $userId);

    public function findCollectionsByUserId($userId);

    public function syncFile($file);

    public function getFileByHashId($hashId);

    public function getFileByConvertHash($hash);

    public function findFilesByIds(array $ids, $showCloud = 0, $params = array());

    public function searchFiles($conditions, $sort, $start, $limit);

    public function searchLiveCloudFiles($conditions, $sort, $start, $limit);

    public function searchFileCount($conditions);

    public function addFile($targetType, $targetId, array $fileInfo = array(), $implemtor = 'local', UploadedFile $originalFile = null);

    public function renameFile($id, $newFilename);

    public function deleteFile($id);

    public function deleteFiles(array $ids);

    public function convertFile($id, $status, array $result = array(), $callback = null);

    public function saveConvertResult($id, array $result = array());

    public function saveConvertResult3($id, array $result = array());

    public function setFileConverting($id, $convertHash);

    public function makeUploadParams($params);

    public function getFileByTargetType($targetType);

    public function tryManageFile($fileId);

    public function tryManageGlobalFile($globalFileId);

    public function tryAccessFile($fileId);

    public function canManageFile($fileId);

    public function findMySharingContacts($targetUserId);

    public function shareFiles($sourceUserId, $targetUserIds);

    public function addShare($sourceUserId, $targetUserId);

    public function updateShare($shareHistoryId);

    public function findShareHistoryByUserId($sourceUserId, $targetUserId);

    public function findShareHistory($sourceUserId);

    public function findActiveShareHistory($sourceUserId);

    public function cancelShareFile($sourceUserId, $targetUserId);

    public function searchShareHistoryCount($conditions);

    public function searchShareHistories($conditions, $orderBy, $start, $limit);

    /**
     * @deprecated This method only wave usedCount, please call waveUsedCount
     *
     * @param  $id
     * @param  $field
     * @param  $diff
     *
     * @return mixed
     */
    public function waveUploadFile($id, $field, $diff);

    public function waveUsedCount($id, $num);

    public function countUseFile($conditions);

    public function searchUseFiles($conditions, $bindFile = true);

    //file-used api
    public function createUseFiles($fileIds, $targetId, $targetType, $type);

    public function batchCreateUseFiles($useFiles);

    public function getUseFile($id);

    public function deleteUseFile($id);

    public function findUseFilesByTargetTypeAndTargetIdAndType($targetType, $targetId, $type);
}
