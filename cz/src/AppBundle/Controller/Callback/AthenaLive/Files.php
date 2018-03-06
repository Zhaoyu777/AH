<?php

namespace AppBundle\Controller\Callback\AthenaLive;

use AppBundle\Common\ArrayToolkit;
use Symfony\Component\HttpFoundation\Request;

class Files extends AthenaLiveBase
{
    public function fetch(Request $request)
    {
        $start = $request->query->get('start', 0);
        $limit = $request->query->get('limit', 100);
        $token = $request->query->get('token');
        $name = $request->query->get('name', '');
        $courseId = $request->query->get('courseId');

        $userToken = $this->getTokenService()->verifyToken('live.callback', $token);

        if (!$userToken) {
            throw $this->createAccessDeniedException('token error');
        }

        if ($userToken['data']['type'] == 'open_course') {
            return array();
        }

        $course = $this->getCourseService()->getCourse($courseId);

        $conditions = array(
            'courseSetId' => $course['courseSetId'],
            'source' => 'coursematerial',
        );

        if ($name) {
            $conditions['title'] = $name;
        }

        $materials = $this->getCourseMaterialService()->searchMaterials($conditions, array('createdTime' => 'DESC'), $start, $limit);

        if (empty($materials)) {
            return array();
        }

        $files = $this->getUploadFileService()->searchLiveCloudFiles(
            array(
                'ids' => ArrayToolkit::column($materials, 'fileId'),
                'storage' => 'cloud',
                'types' => array('document', 'ppt'),
            ),
            array('createdTime' => 'DESC'),
            $start,
            $limit
        );

        return $this->buildCloudFileData($files);
    }

    protected function buildCloudFileData($sourceCourseFiles)
    {
        $cloudFiles = array();
        foreach ($sourceCourseFiles as $sourceCourseFile) {
            $cloudFile['mediaId'] = $sourceCourseFile['globalId'];
            $cloudFile['name'] = $sourceCourseFile['filename'];
            $cloudFile['type'] = $sourceCourseFile['type'];
            $cloudFile['status'] = $sourceCourseFile['status'];

            $cloudFiles['data'][] = $cloudFile;
        }
        $cloudFiles['total'] = count($sourceCourseFiles);

        return $cloudFiles;
    }

    public function create(Request $request)
    {
        $token = $request->query->get('token');
        $courseId = $request->query->get('courseId');

        $userToken = $this->getTokenService()->verifyToken('live.callback', $token);

        if (!$userToken) {
            throw $this->createAccessDeniedException('token error');
        }

        if ($userToken['data']['courseId'] != $courseId) {
            throw $this->createAccessDeniedException(sprintf('token course id 不匹配', $userToken['data']));
        }

        $type = $userToken['data']['type'];

        if ($type == 'open_course') {
            $course = $this->getOpenCourseService()->getCourse($courseId);
        } else {
            $course = $this->getCourseService()->getCourse($courseId);
        }

        $file['globalId'] = $request->request->get('globalId');
        $file['key'] = $request->request->get('hashId');
        $file['filename'] = $request->request->get('filename');
        $file['size'] = $request->request->get('size');
        $file['length'] = $request->request->get('length', 0);
        $file['convertHash'] = $file['key'];
        $file['convertParams'] = array();
        $file['lazyConvert'] = false;

        try {
            if ($type == 'open_course') {
                $this->getUploadFileService()->addFile('opencoursematerial', $course['id'], $file, 'cloud');
            } else {
                $this->getUploadFileService()->addFile('coursematerial', $course['courseSetId'], $file, 'cloud');
            }
        } catch (\Exception $e) {
            return array(
                'error' => array(
                    'code' => -1,
                    'message' => sprintf('添加文件出错: %s', $e->getMessage()),
                ),
            );
        }

        return array(
            'success' => true,
        );
    }

    /**
     * @return \Biz\Course\Service\CourseService
     */
    protected function getCourseService()
    {
        return $this->createService('Course:CourseService');
    }

    /**
     * @return \Biz\File\Service\UploadFileService
     */
    protected function getUploadFileService()
    {
        return $this->createService('File:UploadFileService');
    }

    /**
     * @return \Biz\User\Service\TokenService
     */
    protected function getTokenService()
    {
        return $this->createService('User:TokenService');
    }

    /**
     * @return \Biz\Course\Service\MaterialService
     */
    protected function getCourseMaterialService()
    {
        return $this->createService('Course:MaterialService');
    }

    /**
     * @return \Biz\OpenCourse\Service\OpenCourseService
     */
    protected function getOpenCourseService()
    {
        return $this->createService('OpenCourse:OpenCourseService');
    }
}
