<?php
namespace CustomBundle\Controller\Weixin;

use AppBundle\Common\ArrayToolkit;
use CustomBundle\Controller\Weixin\WeixinBaseController;
use Symfony\Component\HttpFoundation\Request;
use Biz\Course\Service\MaterialService;

class ResourceController extends WeixinBaseController
{
    public function resourcesAction(Request $request, $courseId)
    {
        $request = $request->query->all();
        $user = $this->getCurrentUser();
        $Materials = $this->gerMaterialService()->findCourseMaterials($courseId, 0, 100);

        foreach ($Materials as $key => $Material) {
            if ($Material['lessonId'] == 0) {
                unset($Materials[$key]);
            }
        }

        $fileIds = ArrayToolkit::column($Materials, 'fileId');
        $files = $this->getUploadFile()->findFilesByIds($fileIds);

        $course = $this->getCourseService()->getCourse($courseId);
        $courseSet = $this->getCourseSetService()->getCourseSet($course['courseSetId']);

        return $this->createJsonResponse(array(
            'files' => $this->resourcesSort($files),
            'courseTitle' => $courseSet['title'],
        ));
    }

    protected function resourcesSort($files)
    {
        $results = array(
            array(
                'type'=>'video',
                'files'=>array(),
            ),
            array(
                'type'=>'audio',
                'files'=>array(),
            ),
            array(
                'type'=>'doc',
                'files'=>array(),
            ),
            array(
                'type'=>'ppt',
                'files'=>array(),
            ),
        );
        $result = array();
        foreach ($files as $key => $file) {
            $result['id'] = $file['id'];
            $result['title'] = $file['filename'];
            $result['cover'] = $file['convertHash'];
            $result['date'] = date("Y-m-d H:i:s", $file['createdTime']);

            if ($file['type'] == 'ppt') {
                $results[3]['files'][] = $result;
            } elseif ($file['type'] == 'document') {
                $results[2]['files'][] = $result;
            } elseif ($file['type'] == 'audio') {
                $results[1]['files'][] = $result;
            } elseif ($file['type'] == 'video') {
                $results[0]['files'][] = $result;
            }
        }

        foreach ($results as $key => $result) {
            if (empty($result['files'])) {
                unset($results[$key]);
            }
        }

        return $results;
    }

    protected function getCourseSetService()
    {
        return $this->createService('Course:CourseSetService');
    }

    protected function getCourseService()
    {
        return $this->createService('Course:CourseService');
    }

    protected function gerMaterialService()
    {
        return $this->createService('Course:MaterialService');
    }

    protected function getUploadFile()
    {
        return $this->createService('File:UploadFileService');
    }
}
