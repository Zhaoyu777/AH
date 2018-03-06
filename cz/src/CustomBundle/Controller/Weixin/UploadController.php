<?php

namespace CustomBundle\Controller\Weixin;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use CustomBundle\Controller\Weixin\WeixinBaseController;

class UploadController extends WeixinBaseController
{
    public function pictureDownloadAction(Request $request)
    {
        $user = $this->getCurrentUser();
        $taskId = $request->query->get('taskId');
        $task = $this->getTaskService()->getTask($taskId);

        $status = $this->getTaskStatusService()->getStatusByTaskId($taskId);

        $lessonTask = $this->getCourseLessonService()->getLessonTaskByTaskId($taskId);

        $lesson = $this->getCourseLessonService()->getCourseLesson($lessonTask['lessonId']);
        if (empty($status) || $status['status'] != 'start' || $lesson['status'] != 'teaching') {
            return $this->createJsonResponse(array('message' => '活动不存在或者未开始或已结束'));
        }

        $media_id = $request->query->get('media_id');

        $service = 'get'.ucwords($task['type']).'ResultService';

        $this->$service()->uploadContent($taskId, $media_id);

        return $this->createJsonResponse(array());
    }

    public function imageUploadAction(Request $request)
    {
        if ($request->getMethod() != 'POST') {
            throw $this->createAccessDeniedException('请求失败');
        }

        $file = $request->files->all();

        $record = $this->getFileService()->uploadFile('group', $file['img']);
        $parsed = $this->getFileService()->parseFileUri($record['uri']);

        return $this->createJsonResponse(array(
            'data' => array('url' => "/files/".$parsed['path'])
        ));
    }

    protected function getDisplayWallResultService()
    {
        return $this->createService('CustomBundle:DisplayWall:ResultService');
    }

    protected function getPracticeResultService()
    {
        return $this->createService('CustomBundle:Practice:PracticeResultService');
    }

    protected function getTaskGroupService()
    {
        return $this->createService('CustomBundle:TaskGroup:TaskGroupService');
    }

    protected function getTaskStatusService()
    {
        return $this->createService('CustomBundle:Task:TaskStatusService');
    }

    protected function getActivityService()
    {
        return $this->createService('Activity:ActivityService');
    }

    protected function getFileService()
    {
        return $this->createService('CustomBundle:File:FileService');
    }
}
