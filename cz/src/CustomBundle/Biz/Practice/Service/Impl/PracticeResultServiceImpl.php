<?php

namespace CustomBundle\Biz\Practice\Service\Impl;

use CustomBundle\Common\Platform\PlatformFactory;
use CustomBundle\Common\BeanstalkClient;
use Biz\BaseService;
use AppBundle\Common\ArrayToolkit;
use Codeages\Biz\Framework\Event\Event;
use CustomBundle\Biz\Practice\Service\PracticeResultService;

class PracticeResultServiceImpl extends BaseService implements PracticeResultService
{
    /**
     * table activity_practice_result
     */
    public function createResult($result)
    {
        if (!ArrayToolkit::requireds($result, array('activityId', 'courseId', 'courseTaskId', 'userId'))) {
            throw $this->createInvalidArgumentException('Lack of required fields');
        }

        $result = ArrayToolkit::parts($result, array(
            'activityId',
            'courseId',
            'courseTaskId',
            'userId',
            'isTeacher'
        ));
        $user = $this->getCurrentUser();
        $result['opUserId'] = $user['id'];
        $isStudent = $this->getCourseMemberService()->isCourseStudent($result['courseId'], $result['userId']);

        $this->beginTransaction();
        try {
            $created = $this->getResultDao()->create($result);
            $this->dispatchEvent('practice.create', new Event($created));
            if ($isStudent) {
                $taskResult = $this->getTaskService()->finishTaskResult($result['courseTaskId']);
            }

            $this->commit();

            return $created;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function deleteResult($resultId)
    {
        $result = $this->getResult($resultId);

        if (empty($result)) {
            return ;
        }

        $this->getResultDao()->delete($resultId);

        $this->dispatchEvent('practice.result.delete', new Event($result));
    }

    public function updateResult($resultId, $fields)
    {
        $fields =  ArrayToolkit::parts($fields, array(
            'isCollected',
        ));

        return $this->getResultDao()->update($resultId, $fields);
    }

    public function deleteResultsByTaskIds($taskIds)
    {
        $results = $this->getResultDao()->findByTaskIds($taskIds);

        foreach ($results as $result) {
            $this->deleteResult($result['id']);
        }
    }

    public function deleteContent($contentId)
    {
        $content = $this->getContent($contentId);

        if (empty($content)) {
            throw $this->createNotFoundException("content#{$contentId} Not Found");
        }

        $this->getContentDao()->delete($contentId);

        //$this->dispatchEvent('practice.content.delete', new Event($content));
    }

    public function deleteContentsByResultId($resultId)
    {
        $contents = $this->getContentDao()->findByResultId($resultId);

        foreach ($contents as $content) {
            $this->deleteContent($content['id']);
        }
    }

    public function deletePostsByContentId($contentId)
    {
        return $this->getPostDao()->deleteByContentId($contentId);
    }

    public function remark($resultId, $fields)
    {
        $result = $this->getResult($resultId);

        if (empty($result)) {
            throw $this->createNotFoundException('该结果不存在。');
        }

        $fields = ArrayToolkit::parts($fields, array('remark', 'score'));
        $user = $this->getCurrentUser();
        $fields['opUserId'] = $user['id'];
        if (!empty($fields['remark'])) {
            $fields['remark'] = array_filter($fields['remark']);
        }

        $result = $this->getResultDao()->update($resultId, $fields);
        $this->dispatchEvent('practice.remark', new Event($result));
        $this->dispatchEvent('task.result.remark', new Event($result));

        return $result;
    }

    public function findResultsByTaskId($taskId)
    {
        return $this->getResultDao()->findByTaskId($taskId);
    }

    public function findResultsByActivityId($activityId, $count = PHP_INT_MAX)
    {
        return $this->getResultDao()->findByActivityId($activityId, $count);
    }

    public function findResultsByActivityIdAndIsTeacher($activityId, $isTeacher)
    {
        return $this->getResultDao()->findByActivityIdAndIsTeacher($activityId, $isTeacher);
    }

    public function findResultsByActivityIdWithContents($activityId, $count = PHP_INT_MAX)
    {
        $results = $this->getResultDao()->findByActivityId($activityId, $count);
        $resultIds = ArrayToolkit::column($results, 'id');

        $contents = $this->findContentsByResultIds($resultIds);
        $userIds = ArrayToolkit::column($contents, 'userId');
        $contents = ArrayToolkit::group($contents, 'resultId');
        $users = $this->getUserService()->findUsersByIds($userIds);

        /*
         * 修改展示墙后此处取重写
         */
        foreach ($results as $key => $result) {
            if (!empty($contents[$result['id']])) {
                $results[$key]['contents'] = $contents[$result['id']]; // 后续删除
                $results[$key]['content'] = $contents[$result['id']][0];
                $results[$key]['content']['truename'] = $users[$results[$key]['content']['userId']]['truename'];
                $results[$key]['number'] = $users[$results[$key]['content']['userId']]['number'];
            } else {
                unset($results[$key]);
            }
        }

        return $results;
    }

    public function getResult($id)
    {
        return $this->getResultDao()->get($id);
    }
    public function uploadContent($taskId, $media_id)
    {
        $user = $this->getCurrentUser();
        if ($this->isOpenWorker()) {
            BeanstalkClient::putTubeMessage('WeixinUploadWorker', array(
                'taskId' => $taskId,
                'media_id' => $media_id,
                'userId' => $user['id'],
            ));
        } else {
            $this->uploadContentProcess($taskId, $media_id, $user['id']);
        }
    }

    public function uploadContentProcess($taskId, $media_id, $userId)
    {
        $task = $this->getTaskService()->getTask($taskId);
        $isTeacher = $this->getCourseMemberService()->isCourseTeacher($task['courseId'], $userId);

        $result = array();
        if (!$isTeacher) {
            $result = $this->getResultByUserIdAndTaskId($userId, $taskId);
        }

        $client = PlatformFactory::create($this->biz);

        $file = $client->uploadImg($media_id, 'practice', 1, $userId);

        if (empty($file)) {
            throw $this->createInvalidArgumentException('图片上传出错');
        }

        $file = $this->getFileService()->createFile('weixin', $file);

        $this->beginTransaction();
        try {
        if (empty($result)) {
            $data = array(
                'activityId' => $task['activityId'],
                'courseId' => $task['courseId'],
                'courseTaskId' => $taskId,
                'userId' => $userId,
                'isTeacher' => $isTeacher ? 1 : 0,
            );
            $result = $this->createResult($data);

            $content = array(
                'resultId' => $result['id'],
                'userId' => $userId,
                'uri' => $file['uri'],
            );
            $content = $this->createContent($content);
        } else {
            $fields = array('isCollected' => '0');
            $result = $this->getResultDao()->update($result['id'], $fields);
            $content = $this->getContentByResultId($result['id']);

            $content = $this->updateContent($content['id'], array(
                'uri' => $file['uri'],
                'userId' => $userId,
            ));
        }
        $this->commit();

        return $content;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function getResultByUserIdAndTaskId($userId, $taskId, $withContent = false)
    {
        $result = $this->getResultDao()->getByUserIdAndTaskId($userId, $taskId);
        if (!empty($result) && $withContent) {
            $result['content'] = $this->getContentByResultId($result['id']);
            $user = $this->getUserService()->getUser($userId);
            $result['content']['truename'] = $user['truename'];
            $result['content']['number'] = $user['number'];
        }

        return $result;
    }

    public function createContent($content)
    {
        if (!ArrayToolkit::requireds($content, array('resultId', 'uri'))) {
            throw $this->createInvalidArgumentException('Lack of required fields');
        }

        $content = ArrayToolkit::parts($content, array(
            'userId',
            'resultId',
            'uri',
        ));

        if (empty($content['userId'])) {
            $user = $this->getCurrentUser();
            $content['userId'] = $user['id'];
        }

        $this->beginTransaction();
        try {
            $created = $this->getContentDao()->create($content);

            $this->dispatchEvent('practice.content.create', new Event($created));
            $this->commit();

            return $created;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function updateContent($resultId, $fields)
    {
        $fields = ArrayToolkit::parts($fields, array(
            'uri',
            'userId',
        ));

        $affected = $this->getContentDao()->update($resultId, $fields);
        $this->dispatchEvent('change.practice.image', $affected);

        return $affected;
    }

    public function getContent($contentId)
    {
        return $this->getContentDao()->get($contentId);
    }

    public function getContentByResultId($resultId)
    {
        return $this->getContentDao()->getByResultId($resultId);
    }

    public function findContentsByResultIds($resultIds)
    {
        return $this->getContentDao()->findByResultIds($resultIds);
    }

    public function like($contentId)
    {
        $user = $this->getCurrentUser();

        if (!$user->isLogin()) {
            throw $this->createNotFoundException('你未登录,不能点赞。');
        }

        $content = $this->getContent($contentId);

        if (empty($content)) {
            throw $this->createNotFoundException('该图片不存在。');
        }

        $like = $this->getLikeByContentIdAndUserId($contentId, $user['id']);

        if (!empty($like)) {
            return $like;
        }

        $like = array(
            'contentId' => $contentId,
            'userId' => $user['id'],
        );

        $this->beginTransaction();
        try {
            $this->getContentDao()->wave(array($like['contentId']), array('likeNum' => +1));

            $created = $this->getLikeDao()->create($like);
            // @todo 推送
            $this->dispatchEvent('practice.content.like', $created);

            $this->commit();

            return $created;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function cancelLike($contentId)
    {
        $user = $this->getCurrentUser();

        if (!$user->isLogin()) {
            throw $this->createNotFoundException('你还未登录,不能取消点赞。');
        }

        $content = $this->getContent($contentId);

        if (empty($content)) {
            throw $this->createNotFoundException('该图片不存在。');
        }

        $like = $this->getLikeByContentIdAndUserId($contentId, $user['id']);

        if (empty($like)) {
            return $like;
        }

        $this->beginTransaction();
        try {
            $this->getLikeDao()->deleteByContentIdAndUserId($contentId, $user['id']);
            $this->getContentDao()->wave(array($contentId), array('likeNum' => -1));

            // @todo 推送
            $this->dispatchEvent('practice.content.cancelLike', $like);
            $this->commit();
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function isLike($contentId)
    {
        $user = $this->getCurrentUser();

        $content = $this->getContent($contentId);

        if (empty($content)) {
            throw $this->createNotFoundException('该图片不存在。');
        }

        $like = $this->getLikeByContentIdAndUserId($contentId, $user['id']);

        return !empty($like);
    }

    public function deleteLikesByContentId($contentId)
    {
        return $this->getLikeDao()->deleteByContentId($contentId);
    }

    public function getLikeByContentIdAndUserId($contentId, $userId)
    {
        return $this->getLikeDao()->getByContentIdAndUserId($contentId, $userId);
    }

    public function findLikesByContentIdsAndUserId($contentIds, $userId)
    {
        if (empty($contentIds)) {
            return array();
        }

        return ArrayToolkit::index($this->getLikeDao()->findByContentIdsAndUserId($contentIds, $userId), 'contentId');
    }

    public function getContentByResultIdAndUserId($resultId, $userId)
    {
        return $this->getContentDao()->getByResultIdAndUserId($resultId, $userId);
    }

    public function createPost($fields)
    {
        if (!ArrayToolkit::requireds($fields, array('contentId'))) {
            throw $this->createInvalidArgumentException('Lack of required fields');
        }

        $post = ArrayToolkit::parts($fields, array(
            'contentId',
            'content',
            'parentId',
        ));
        $user = $this->getCurrentUser();
        $post['userId'] = $user['id'];

        $this->beginTransaction();
        try {
            $created = $this->getPostDao()->create($post);
            $this->getContentDao()->wave(array($post['contentId']), array('postNum' => +1));

            $this->commit();
            $this->dispatchEvent('practice.post.create', $created);

            return $created;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function getPost($id)
    {
        return $this->getPostDao()->get($id);
    }

    public function findPostsByContentId($contentId)
    {
        return $this->getPostDao()->findByContentId($contentId);
    }

    public function deletePost($id)
    {
        $post = $this->getPost($id);
        if (empty($post)) {
            return;
        }

        $this->beginTransaction();
        try {
            $this->getPostDao()->delete($id);
            $this->getContentDao()->wave(array($post['contentId']), array('postNum' => -1));

            $this->commit();
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function deletePostsByContendId($contentId)
    {
        return $this->getPostDao()->deleteByContentId($contentId);
    }

    public function cancelCourseLessonProcess($taskIds)
    {
        $results = $this->getResultDao()->findByTaskIds($taskIds);
        $this->deleteResultsByTaskIds($taskIds);

        foreach ($results as $result) {
            $content = $this->getContentByResultId($result['id']);
            $this->deleteContentsByResultId($result['id']);

            $this->deletePostsByContentId($content['id']);

            $this->deleteLikesByContentId($content['id']);
        }
    }

    protected function getContentDao()
    {
        return $this->createDao('CustomBundle:Practice:ContentDao');
    }

    protected function getLikeDao()
    {
        return $this->createDao('CustomBundle:Practice:LikeDao');
    }

    protected function isOpenWorker()
    {
        $magic = $this->createService('System:SettingService')->get('magic');

        if (isset($magic['open_worker']) && $magic['open_worker']) {
            return true;
        }

        return false;
    }

    protected function getUserService()
    {
        return $this->createService('User:UserService');
    }

    protected function getResultDao()
    {
        return $this->createDao('CustomBundle:Practice:ResultDao');
    }

    protected function getPostDao()
    {
        return $this->createDao('CustomBundle:Practice:PostDao');
    }

    protected function getFileService()
    {
        return $this->createService('CustomBundle:File:FileService');
    }

    protected function getTaskService()
    {
        return $this->createService('CustomBundle:Task:TaskService');
    }

    protected function getCourseMemberService()
    {
        return $this->createService('CustomBundle:Course:MemberService');
    }
}
