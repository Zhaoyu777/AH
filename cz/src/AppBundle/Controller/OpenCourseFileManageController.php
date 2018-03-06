<?php

namespace AppBundle\Controller;

use AppBundle\Common\Paginator;
use AppBundle\Common\ArrayToolkit;
use Biz\File\Service\UploadFileService;
use Biz\OpenCourse\Service\OpenCourseService;
use Symfony\Component\HttpFoundation\Request;

class OpenCourseFileManageController extends BaseController
{
    public function indexAction(Request $request, $id)
    {
        $course = $this->getOpenCourseService()->tryManageOpenCourse($id);

        $conditions = array(
            'courseId' => $course['id'],
            'type' => 'openCourse',
        );

        $paginator = new Paginator(
            $request,
            $this->getMaterialService()->searchMaterialCountGroupByFileId($conditions),
            20
        );

        //FIXME 同一个courseId下文件可能存在重复，所以需考虑去重，但没法直接根据groupbyFileId去重（sql_mode）
        $materials = $this->getMaterialService()->searchMaterials(
            $conditions,
            array('createdTime' => 'DESC'),
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        $files = $this->getMaterialService()->findFullFilesAndSort($materials);
        $fileIds = ArrayToolkit::column($files, 'fileId');
        $filesQuote = $this->getMaterialService()->findUsedCourseMaterials($fileIds, $id);

        $users = $this->getUserService()->findUsersByIds(ArrayToolkit::column($files, 'updatedUserId'));

        return $this->render('open-course-manage/material-list.html.twig', array(
            'courseSet' => $course,
            'course' => $course,
            'files' => $files,
            'users' => ArrayToolkit::index($users, 'id'),
            'paginator' => $paginator,
            'now' => time(),
            'filesQuote' => $filesQuote,
        ));
    }

    public function showAction(Request $request, $id, $fileId)
    {
        $course = $this->getOpenCourseService()->tryManageOpenCourse($id);
        $file = $this->getUploadFileService()->getFile($fileId);

        $materialCount = $this->getMaterialService()->countMaterials(
            array(
                'courseId' => $id,
                'fileId' => $fileId,
            )
        );

        if (!$materialCount) {
            throw $this->createNotFoundException();
        }

        $file = $this->getUploadFileService()->getFile($fileId);

        if (empty($file)) {
            throw $this->createNotFoundException();
        }

        return $this->forward('AppBundle:UploadFile:download', array('fileId' => $file['id']));
    }

    public function convertAction(Request $request, $id, $fileId)
    {
        $course = $this->getOpenCourseService()->tryManageOpenCourse($id);

        $file = $this->getUploadFileService()->getFile($fileId);

        if (empty($file)) {
            throw $this->createNotFoundException();
        }

        $convertHash = $this->getUploadFileService()->reconvertFile($file['id']);

        if (empty($convertHash)) {
            return $this->createJsonResponse(array('status' => 'error', 'message' => $this->getServiceKernel()->trans('文件转换请求失败，请重试！')));
        }

        return $this->createJsonResponse(array('status' => 'ok'));
    }

    public function deleteCourseFilesAction(Request $request, $id)
    {
        $course = $this->getOpenCourseService()->tryManageOpenCourse($id);

        if ($request->getMethod() == 'POST') {
            $formData = $request->request->all();

            $this->getMaterialService()->deleteMaterials($id, $formData['ids'], 'openCourse');

            if (isset($formData['isDeleteFile']) && $formData['isDeleteFile']) {
                foreach ($formData['ids'] as $key => $fileId) {
                    if ($this->getUploadFileService()->canManageFile($fileId)) {
                        $this->getUploadFileService()->deleteFile($fileId);
                    }
                }
            }

            return $this->createJsonResponse(true);
        }

        return $this->render('courseset-manage/file/file-delete-modal.html.twig', array(
            'course' => $course,
            'courseSet' => $course,
        ));
    }

    public function deleteMaterialShowAction(Request $request, $id)
    {
        $course = $this->getOpenCourseService()->tryManageOpenCourse($id);

        $fileIds = $request->request->get('ids');
        $materials = $this->getMaterialService()->findUsedCourseMaterials($fileIds, $id);
        $files = $this->getUploadFileService()->findFilesByIds($fileIds, 0);
        $files = ArrayToolkit::index($files, 'id');

        return $this->render('courseset-manage/file/file-delete-modal.html.twig', array(
            'course' => $course,
            'courseSet' => $course,
            'materials' => $materials,
            'files' => $files,
            'ids' => $fileIds,
        ));
    }

    public function lessonMaterialModalAction(Request $request, $courseId, $lessonId)
    {
        $course = $this->getOpenCourseService()->tryManageOpenCourse($courseId);
        $lesson = $this->getOpenCourseService()->getCourseLesson($courseId, $lessonId);

        $materials = $this->getMaterialService()->searchMaterials(
            array('lessonId' => $lesson['id'], 'type' => 'openCourse'),
            array('createdTime' => 'DESC'),
            0,
            100
        );

        return $this->render('open-course-manage/material-edit-modal.html.twig', array(
            'course' => $course,
            'lesson' => $lesson,
            'materials' => $materials,
            'storageSetting' => $this->setting('storage'),
            'targetType' => 'coursematerial',
            'targetId' => $course['id'],
        ));
    }

    public function fileStatusAction(Request $request)
    {
        $currentUser = $this->getCurrentUser();

        if (!$currentUser->isTeacher() && !$currentUser->isAdmin()) {
            return $this->createJsonResponse(array());
        }

        $fileIds = $request->request->get('ids');

        if (empty($fileIds)) {
            return $this->createJsonResponse(array());
        }

        $fileIds = explode(',', $fileIds);

        return $this->createJsonResponse($this->getUploadFileService()->findFilesByIds($fileIds, 1));
    }

    /**
     * @return OpenCourseService
     */
    protected function getOpenCourseService()
    {
        return $this->getBiz()->service('OpenCourse:OpenCourseService');
    }

    /**
     * @return UploadFileService
     */
    protected function getUploadFileService()
    {
        return $this->getBiz()->service('File:UploadFileService');
    }

    protected function getMaterialService()
    {
        return $this->getBiz()->service('Course:MaterialService');
    }
}
