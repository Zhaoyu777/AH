<?php

namespace AppBundle\Controller;

use Biz\Course\Service\CourseService;
use Biz\File\Service\UploadFileService;
use Biz\Subtitle\Service\SubtitleService;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Common\Exception\ResourceNotFoundException;

class SubtitleController extends BaseController
{
    public function manageAction(Request $request, $mediaId)
    {
        if (!$this->getUploadFileService()->canManageFile($mediaId)) {
            throw $this->createAccessDeniedException('没有权限管理资源');
        }

        $ssl = $request->isSecure() ? true : false;
        $subtitles = $this->getSubtitleService()->findSubtitlesByMediaId($mediaId, $ssl);

        $media = $this->getUploadFileService()->getFile($mediaId);
        if (empty($media) || !in_array($media['type'], array('video', 'audio'))) {
            throw new ResourceNotFoundException('uploadFile', $mediaId);
        }

        return $this->render('media-manage/subtitle/manage.html.twig', array(
            'media' => $media,
            'goto' => $request->query->get('goto'),
            'subtitles' => $subtitles,
        ));
    }

    /**
     * 获取某一视频下所有的字幕.
     */
    public function listAction(Request $request, $mediaId)
    {
        if (!$this->getUploadFileService()->canManageFile($mediaId)) {
            throw $this->createAccessDeniedException('没有权限管理资源');
        }

        $ssl = $request->isSecure() ? true : false;
        $subtitles = $this->getSubtitleService()->findSubtitlesByMediaId($mediaId, $ssl);

        return $this->createJsonResponse(array(
            'subtitles' => $subtitles,
        ));
    }

    public function createAction(Request $request, $mediaId)
    {
        if (!$this->getUploadFileService()->canManageFile($mediaId)) {
            throw $this->createAccessDeniedException('没有权限管理资源');
        }

        $fileds = $request->request->all();

        $subtitle = $this->getSubtitleService()->addSubtitle($fileds);

        return $this->createJsonResponse($subtitle);
    }

    public function deleteAction($mediaId, $id)
    {
        if (!$this->getUploadFileService()->canManageFile($mediaId)) {
            throw $this->createAccessDeniedException('没有权限管理资源');
        }

        $this->getSubtitleService()->deleteSubtitle($id);

        return $this->createJsonResponse(true);
    }

    public function previewAction($mediaId)
    {
        return $this->render('media-manage/preview.html.twig', array(
            'mediaId' => $mediaId,
            'context' => array(
                'hideQuestion' => 1,
                'hideBeginning' => true,
            ),
        ));
    }

    public function manageDialogAction(Request $request)
    {
        $mediaId = $request->query->get('mediaId');
        if (!$this->getUploadFileService()->canManageFile($mediaId)) {
            throw $this->createAccessDeniedException('没有权限管理资源');
        }

        $ssl = $request->isSecure() ? true : false;
        $subtitles = $this->getSubtitleService()->findSubtitlesByMediaId($mediaId, $ssl);

        $media = $this->getUploadFileService()->getFile($mediaId);

        return $this->render('media-manage/subtitle/dialog.html.twig', array(
            'subtitles' => $subtitles,
            'media' => $media,
        ));
    }

    /**
     * @return CourseService
     */
    protected function getCourseService()
    {
        return $this->getBiz()->service('Course:CourseService');
    }

    /**
     * @return UploadFileService
     */
    protected function getUploadFileService()
    {
        return $this->getBiz()->service('File:UploadFileService');
    }

    /**
     * @return SubtitleService
     */
    protected function getSubtitleService()
    {
        return $this->getBiz()->service('Subtitle:SubtitleService');
    }
}
