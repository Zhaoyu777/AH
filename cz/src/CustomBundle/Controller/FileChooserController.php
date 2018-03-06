<?php

namespace CustomBundle\Controller;

use AppBundle\Common\Paginator;
use Biz\User\Service\UserService;
use AppBundle\Common\ArrayToolkit;
use Biz\Course\Service\CourseService;
use Biz\Course\Service\MaterialService;
use Biz\File\Service\UploadFileService;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\MediaParser\ParserProxy;
use AppBundle\Controller\FileChooserController as BaseController;
/**
 * Class MediaProccessController
 * 用来处理活动中文件选取(上传，从资料库选择，从课程文件选择，导入网络文件)逻辑.
 */
class FileChooserController extends BaseController
{
    public function materialChooseAction(Request $request)
    {
        $currentUser = $this->getUser();

        if (!$currentUser->isTeacher() && !$currentUser->isAdmin()) {
            throw $this->createAccessDeniedException('Permission denied, you can not access this page!');
        }
        $conditions = $request->query->all();
        $conditions = $this->filterMaterialConditions($conditions, $currentUser);
        $paginator = new Paginator(
            $request,
            $this->getUploadFileService()->searchFileCount($conditions),
            20
        );
        $files = $this->getUploadFileService()->searchFiles(
            $conditions,
            array('createdTime' => 'DESC'),
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        $createdUsers = $this->getUserService()->findUsersByIds(ArrayToolkit::column($files, 'createdUserId'));
        $createdUsers = ArrayToolkit::index($createdUsers, 'id');

        return $this->render(
            'file-chooser/widget/choose-table.html.twig',
            array(
                'files' => $files,
                'createdUsers' => $createdUsers,
                'paginator' => $paginator,
            )
        );
    }

    protected function filterMaterialConditions($conditions, $currentUser)
    {
        $conditions['status'] = 'ok';
        $conditions['currentUserId'] = $currentUser['id'];

        $conditions['noTargetType'] = 'attachment';
        if (!empty($conditions['keyword'])) {
            $conditions['filename'] = $conditions['keyword'];
            unset($conditions['keyword']);
        }


        if (empty($conditions['type'])) {
            $conditions['type'] = null;

            return $conditions;
        }

        $tyeps = explode(',', $conditions['type']);
        unset($conditions['type']);

        if (count($tyeps) == 1) {
            $conditions['type'] = reset($tyeps);
        } else {
            $conditions['types'] = $tyeps;
        }

        return $conditions;
    }

    /**
     * @return UploadFileService
     */
    protected function getUploadFileService()
    {
        return $this->createService('CustomBundle:File:UploadFileService');
    }

    /**
     * @return UserService
     */
    protected function getUserService()
    {
        return $this->createService('User:UserService');
    }
}
