<?php

namespace CustomBundle\Controller\Weixin;

use CustomBundle\Common\WeixinClient;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Common\ImgConverToData;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use CustomBundle\Controller\Weixin\WeixinBaseController;

class PracticeWorkController extends WeixinBaseController
{
    public function pictureUploadAction(Request $request)
    {
        $result = $request->query->all();
        $media_id = $result['media_id'];
        $taskId = $result['taskId'];
        $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($taskId);
        if ($lessonTask['stage'] == 'in') {
            $status = $this->getTaskStatus($taskId);
            if (empty($status) || $status == 'end') {
                return $this->createJsonResponse(array('message' => '活动未开始或已结束，上传失败!'), 500);
            }
        }
        $client = $this->getPlatformClient();
        $file = $client->uploadImg($media_id, 'practice-work');
        if (empty($file)) {
            return $this->createJsonResponse(array('message' => '图片上传出错!'), 500);
        }
        $group = $this->getGroup('weixin_practice_work');
        $file = $this->getFileService()->createFile($group['code'], $file);
        $result = $this->getPracticeWorkResult($taskId);
        if ($result) {
            $result = $this->getPracticeWorkService()->updateResult($result['id'], array('fileId' => $file['id'], 'origin' => 'weixin', 'finalSubTime' => time()));
        } else {
            $result = $this->createResult($file, $taskId);
        }

        $ssl = $request->isSecure() ? true : false;
        $url = ($ssl ? 'https://' : 'http://').$request->getHost().$this->generateUrl('weixin_practice_work_picture_show', array('type' => $result['origin'], 'id' => $file['id']));

        return $this->createJsonResponse(array(
            'url' => $url
        ));
    }

    public function pictureShowAction(Request $request, $type, $id)
    {
        if ($type == 'weixin') {
            $file = $this->getFileService()->getFile($id);
            $path = $this->getUri($file['uri'], $type);
        } else {
            $file = $this->getUploadFileService()->getFile($id);
            $path = $this->getUri($file['hashId'], $type);
        }
        $imgConverToData = new ImgConverToData();
        $imgConverToData->getImgDir($path);
        $imgConverToData->img2Data();
        $imgData = $imgConverToData->data2Img();
        echo $imgData;
        exit;
    }

    public function resultAction(Request $request, $taskId)
    {
        $user = $this->getCurrentUser();
        $courseTask = $this->getTaskService()->getTask($taskId);
        $activity = $this->getActivityService()->getActivity($courseTask['activityId']);
        $result = $this->getPracticeWorkService()->getResultByTaskIdAndUserId($courseTask['id'], $user['id']);
        $config = $this->getActivityService()->getActivityConfig('practiceWork');
        $practiceWork = $config->get($activity['mediaId']);
        $file = array();
        $pictureUrl = '';
        if ($result['origin'] == 'weixin') {
            $file = $this->getFileService()->getFile($result['fileId']);
        } else {
            $file = $this->getUploadFileService()->getFile($result['fileId']);
        }
        $ssl = $request->isSecure() ? true : false;
        if (!empty($result) && !empty($file)) {
            $pictureUrl = ($ssl ? 'https://' : 'http://').$request->getHost().$this->generateUrl('weixin_practice_work_picture_show', array('type' => $result['origin'], 'id' => $file['id']));
        }

        if (isset($file['storage']) && $file['storage'] == 'cloud') {
            $ssl = $request->isSecure() ? true : false;
            $file = $this->getMaterialLibService()->player($file['globalId'], $ssl);
            $pictureUrl = $file['preview'];
        }

        return $this->createJsonResponse(array(
            'result' => $result ? $result : array(),
            'practiceWork' => $practiceWork,
            'file' => $file,
            'pictureUrl' => $pictureUrl,
            'status' => $this->getTaskStatus($taskId),
        ));
    }

    private function getUri($uri, $type)
    {
        if ($type == 'weixin') {
            $uri = $this->getWebExtension()->parseFileUri($uri);

            return rtrim($this->container->getParameter('topxia.upload.private_directory'), ' /').'/'.$uri['path'];
        }

        return rtrim($this->container->getParameter('topxia.disk.local_directory'), ' /').'/'.$uri;
    }

    private function getPracticeWorkResult($taskId)
    {
        $user = $this->getCurrentUser();
        $courseTask = $this->getTaskService()->getTask($taskId);
        $activity = $this->getActivityService()->getActivity($courseTask['activityId']);

        return $this->getPracticeWorkService()->getResultByTaskIdAndUserId($courseTask['id'], $user['id']);
    }

    private function createResult($file, $taskId)
    {
        $user = $this->getCurrentUser();
        $courseTask = $this->getTaskService()->getTask($taskId);
        $activity = $this->getActivityService()->getActivity($courseTask['activityId']);
        return $this->getPracticeWorkService()->createResult(array(
            'activityId' => $activity['id'],
            'fileId' => $file['id'],
            'practiceWorkId' => $activity['mediaId'],
            'taskId' => $taskId,
            'userId' => $user['id'],
            'origin' => 'weixin',
            'finalSubTime' => time(),
        ));
    }

    private function getGroup($groupCode)
    {
        $group = $this->getFileService()->getFileGroupByCode('weixin_practice_work');
        if (empty($group)) {
            $group = $this->getFileService()->addFileGroup(array('name' => '微信实践作业', 'code' => 'weixin_practice_work'));
        }

        return $group;
    }

    private function getTaskStatus($taskId)
    {
        $status = $this->getStatusService()->getStatusByTaskId($taskId);

        return $status['status'];
    }

    protected function getFileService()
    {
        return $this->createService('CustomBundle:File:FileService');
    }

    protected function getMaterialLibService()
    {
        return $this->createService('MaterialLib:MaterialLibService');
    }

    protected function getUploadFileService()
    {
        return $this->createService('CustomBundle:File:UploadFileService');
    }

    protected function getTaskService()
    {
        return $this->createService('CustomBundle:Task:TaskService');
    }

    protected function getActivityService()
    {
        return $this->createService('CustomBundle:Activity:ActivityService');
    }

    protected function getCourseLessonService()
    {
        return $this->createService('CustomBundle:Course:CourseLessonService');
    }

    protected function getPracticeWorkService()
    {
        return $this->createService('CustomBundle:Activity:PracticeWorkService');
    }

    protected function getStatusService()
    {
        return $this->createService('CustomBundle:Task:TaskStatusService');
    }
}