<?php

namespace CustomBundle\Controller\Activity;

use Symfony\Component\HttpFoundation\Request;
use AppBundle\Controller\BaseController;

class DownloadController extends BaseController
{
    public function showAction(Request $request, $activity, $task)
    {
        $download = $this->getActivityService()->getActivityConfig($activity['mediaType'])->get($activity['mediaId']);
        $materials = $this->getMaterialService()->findMaterialsByLessonIdAndSource($activity['id'], 'coursematerial');
        $course = $this->getCourseService()->getCourse($task['courseId']);

        return $this->render('activity/download/show.html.twig', array(
            'materials' => $materials,
            'activity' => $activity,
            'download' => $download,
            'task' => $task,
            'course' => $course,
        ));
    }

    public function editAction(Request $request, $id, $courseId)
    {
        $activity = $this->getActivityService()->getActivity($id, $fetchMedia = true);
        $materials = $this->getMaterialService()->findMaterialsByLessonIdAndSource($activity['id'], 'coursematerial');

        foreach ($materials as $material) {
            $id = empty($material['fileId']) ? $material['link'] : $material['fileId'];
            $activity['ext']['materials'][$id] = array('id' => $material['fileId'], 'size' => $material['fileSize'], 'name' => $material['title'], 'link' => $material['link']);
        }

        return $this->render('activity/download/modal.html.twig', array(
            'activity' => $activity,
            'courseId' => $courseId,
        ));
    }

    public function downloadFileAction(Request $request, $courseId, $activityId)
    {
        $this->getCourseService()->tryTakeCourse($courseId);

        $materialId = $request->query->get('materialId');
        $downloadFile = $this->getDownloadActivityService()->downloadActivityFile($activityId, $materialId);

        if (!empty($downloadFile['link'])) {
            return $this->redirect($downloadFile['link']);
        } else {
            return $this->forward('AppBundle:UploadFile:download', array(
                'request' => $request,
                'fileId' => $downloadFile['fileId'],
            ));
        }
    }

    public function createAction(Request $request, $courseId)
    {
        return $this->render('activity/download/modal.html.twig', array(
            'courseId' => $courseId,
        ));
    }

    public function previewAction(Request $request, $task)
    {
        $course = $this->getCourseService()->getCourse($task['courseId']);
        $activity = $this->getActivityService()->getActivity($task['activityId'], $fetchMedia = true);
        $download = $this->getActivityService()->getActivityConfig($activity['mediaType'])->get($activity['mediaId']);
        $materials = $this->getMaterialService()->findMaterialsByLessonIdAndSource($activity['id'], 'coursematerial');

        return $this->render('activity/download/preview.html.twig', array(
            'course' => $course,
            'materials' => $materials,
            'activity' => $activity,
            'download' => $download,
            'task' => $task,
        ));
    }

    public function finishConditionAction(Request $request, $activity)
    {
        return $this->render('activity/download/finish-condition.html.twig', array());
    }

    /**
     * @return ActivityService
     */
    protected function getActivityService()
    {
        return $this->createService('Activity:ActivityService');
    }

    /**
     * @return DownloadActivityService
     */
    protected function getDownloadActivityService()
    {
        return $this->createService('Activity:DownloadActivityService');
    }

    /**
     * @return MaterialService
     */
    protected function getMaterialService()
    {
        return $this->createService('Course:MaterialService');
    }

    protected function getCourseService()
    {
        return $this->createService('CustomBundle:Course:CourseService');
    }
}
