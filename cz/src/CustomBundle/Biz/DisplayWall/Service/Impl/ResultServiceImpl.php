<?php

namespace CustomBundle\Biz\DisplayWall\Service\Impl;

use Biz\BaseService;
use CustomBundle\Common\Platform\PlatformFactory;
use CustomBundle\Common\BeanstalkClient;
use AppBundle\Common\ArrayToolkit;
use Codeages\Biz\Framework\Event\Event;
use CustomBundle\Biz\DisplayWall\Service\ResultService;

class ResultServiceImpl extends BaseService implements ResultService
{
    /**
     * table activity_display_wall_result
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
            'groupId',
        ));
        $user = $this->getCurrentUser();
        $result['opUserId'] = $user['id'];

        $activity = $this->getActivityService()->getActivity($result['activityId']);
        $config = $this->getActivityService()->getActivityConfig('displayWall');
        $activity = $config->get($activity['mediaId']);
        $result['memberCount'] = 1;
        if ($activity['submitWay'] == "group") {
            $result['memberCount'] = $this->getTaskGroupService()->countTaskGroupMembersByGroupId($result['groupId']);
        }

        $this->beginTransaction();
        try {
            $created = $this->getResultDao()->create($result);
            $this->dispatchEvent('display.wall.create', new Event($created));

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

        $this->dispatchEvent('display.wall.result.delete', new Event($result));
    }

    public function deleteResultsByTaskIds($taskIds)
    {
        $results = $this->getResultDao()->findByTaskIds($taskIds);

        foreach ($results as $result) {
            $this->deleteResult($result['id']);
        }
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
        $this->dispatchEvent('display.wall.remark', new Event($result));
        $this->dispatchEvent('task.result.remark', new Event($result));

        return $result;
    }

    public function findResultsByTaskId($taskId)
    {
        return $this->getResultDao()->findByTaskId($taskId);
    }

    public function countResultByTaskId($taskId)
    {
        return $this->getResultDao()->countByTaskId($taskId);
    }

    public function findResultsByUserIdsAndTaskId($userIds, $taskId)
    {
        return $this->getResultDao()->findByUserIdsAndTaskId($userIds, $taskId);
    }

    protected function getFixedGroupMemberService()
    {
        return $this->createService('CustomBundle:Course:GroupMemberService');
    }

    public function getResult($id)
    {
        return $this->getResultDao()->get($id);
    }

    public function findResults($id)
    {
        return ;
    }

    public function getResultByUserIdAndTaskId($userId, $taskId, $withContent = false)
    {
        $result = $this->getResultDao()->getByUserIdAndTaskId($userId, $taskId);
        if (!empty($result) && $withContent) {
            $result['content'] = $this->getContentByResultIdAndUserId($result['id'], $userId);
            $user = $this->getUserService()->getUser($userId);
            $result['content']['truename'] = $user['truename'];
            $result['content']['number'] = $user['number'];
        }

        return $result;
    }

    public function getResultByTaskIdAndGroupId($taskId, $groupId)
    {
        return $this->getResultDao()->getByTaskIdAndGroupId($taskId, $groupId);
    }

    public function findResultsByActivityId($activityId, $count = PHP_INT_MAX)
    {
        return $this->getResultDao()->findByActivityId($activityId, $count);
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

    public function findResultsByActivityIdAndUserIdsWithContents($activityId, $userIds)
    {
        if (empty($userIds)) {
            return array();
        }

        $results = $this->getResultDao()->findByActivityIdUserIds($activityId, $userIds);
        $resultIds = ArrayToolkit::column($results, 'id');

        $contents = $this->findContentsByResultIds($resultIds);
        $contents = ArrayToolkit::group($contents, 'resultId');

        array_walk(
            $results,
            function (&$result) use ($contents) {
                $result['contents'] = $contents[$result['id']];
            }
        );

        return $results;
    }

    public function getLastResultByActivityIdAndUserIdsWithContents($activityId, $userIds)
    {
        if (empty($userIds)) {
            return array();
        }

        $result = $this->getResultDao()->getLastByActivityIdAndUserIds($activityId, $userIds);

        $result['contents'] = $this->findContentsByResultId($result['id']);

        return $result;
    }

    /**
     * table activity_display_wall_content
     */
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

            $this->dispatchEvent('display.wall.content.create', new Event($created));
            $this->commit();

            return $created;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function deleteContent($contentId)
    {
        $content = $this->getContent($contentId);

        if (empty($content)) {
            throw $this->createNotFoundException("content#{$contentId} Not Found");
        }

        $this->getContentDao()->delete($contentId);

        $this->dispatchEvent('display.wall.content.delete', new Event($content));
    }

    public function deleteContentsByResultId($resultId)
    {
        $contents = $this->getContentDao()->findByResultId($resultId);

        foreach ($contents as $content) {
            $this->deleteContent($content['id']);
        }
    }

    public function findContentsByUserIds($userIds)
    {
        return $this->getContentDao()->findByUserIds($userIds);
    }

    public function findContentsByResultIds($resultIds)
    {
        return $this->getContentDao()->findByResultIds($resultIds);
    }

    public function findContentsByResultId($resultId)
    {
        return $this->getContentDao()->findByResultId($resultId);
    }

    public function getLastContentByResultId($resultId)
    {
        return $this->getContentDao()->getLastByResultId($resultId);
    }

    public function getContent($contentId)
    {
        return $this->getContentDao()->get($contentId);
    }

    public function getContentByResultIdAndUserId($resultId, $userId)
    {
        return $this->getContentDao()->getByResultIdAndUserId($resultId, $userId);
    }

    public function changeContentImgById($id, $uri)
    {
        return $this->getContentDao()->update(
            $id,
            array('uri' => $uri)
        );
    }

    public function updateContent($resultId, $fields)
    {
        $fields = ArrayToolkit::parts($fields, array(
            'uri',
            'userId',
        ));

        $affected = $this->getContentDao()->update($resultId, $fields);
        $this->dispatchEvent('change_display_wall_image', $affected);

        return $affected;
    }

    /**
     * table activity_display_wall_post
     */
    public function createPost($post)
    {
        if (!ArrayToolkit::requireds($post, array('contentId'))) {
            throw $this->createInvalidArgumentException('Lack of required fields');
        }

        $post = ArrayToolkit::parts($post, array(
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
            $this->dispatchEvent('display.wall.post.create', $created);

            return $created;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function findPostsByContentId($contentId)
    {
        return $this->getPostDao()->findByContentId($contentId);
    }

    public function deletePostsByContentId($contentId)
    {
        return $this->getPostDao()->deleteByContentId($contentId);
    }

    /**
     * table activity_display_wall_like
     */
    public function like($contentId)
    {
        $user = $this->getCurrentUser();

        if (empty($user)) {
            throw $this->createNotFoundException('用户还未登录,不能点赞。');
        }

        $content = $this->getContent($contentId);

        if (empty($content)) {
            throw $this->createNotFoundException('内容不存在，或已删除。');
        }

        $like = $this->getLikeByContentIdAndUserId($contentId, $user['id']);

        if (!empty($like)) {
            return $like;
        }

        $like = array(
            'contentId' => $contentId,
            'userId' => $user['id'],
        );

        $this->getContentDao()->wave(array($like['contentId']), array('likeNum' => +1));

        $created = $this->getLikeDao()->create($like);
        $this->dispatchEvent('display.wall.content.like', $created);

        return $created;
    }

    public function deleteLikesByContentId($contentId)
    {
        return $this->getLikeDao()->deleteByContentId($contentId);
    }

    public function cancelLike($contentId)
    {
        $user = $this->getCurrentUser();

        if (empty($user)) {
            throw $this->createNotFoundException('用户还未登录,不能取消点赞。');
        }

        $content = $this->getContent($contentId);

        if (empty($content)) {
            throw $this->createNotFoundException('内容不存在，或已删除。');
        }

        $like = $this->getLikeByContentIdAndUserId($contentId, $user['id']);

        if (empty($like)) {
            return $like;
        }

        $this->getLikeDao()->deleteByContentIdAndUserId($contentId, $user['id']);
        $this->getContentDao()->wave(array($contentId), array('likeNum' => -1));

        $this->dispatchEvent('display.wall.content.cancelLike', $like);
    }

    public function isLike($contentId)
    {
        $user = $this->getCurrentUser();

        $content = $this->getContent($contentId);

        if (empty($content)) {
            throw $this->createNotFoundException('内容不存在，或已删除。');
        }

        $like = $this->getLikeByContentIdAndUserId($contentId, $user['id']);

        return !empty($like);
    }

    public function findLikesByContentIdsAndUserId($contentIds, $userId)
    {
        if (empty($contentIds)) {
            return array();
        }

        return ArrayToolkit::index($this->getLikeDao()->findByContentIdsAndUserId($contentIds, $userId), 'contentId');
    }

    public function getLikeByContentIdAndUserId($contentId, $userId)
    {
        return $this->getLikeDao()->getByContentIdAndUserId($contentId, $userId);
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
        $activity = $this->getActivityService()->getActivity($task['activityId']);
        $config = $this->getActivityService()->getActivityConfig('displayWall');
        $displayWall = $config->get($activity['mediaId']);
        $groupMember = $this->getTaskGroupService()->getTaskGroupMemberByUserIdAndTaskId($userId, $taskId);

        if ($displayWall['submitWay'] == 'person') {
            $result = $this->getResultByUserIdAndTaskId($userId, $taskId);
        } else {
            $result = $this->getResultByTaskIdAndGroupId($taskId, $groupMember['groupId']);
        }

        // if (!empty($result['score']) && $result['score'] != 0) {
        //     return $this->createJsonResponse(array('message' => '你已完成该任务'));
        // }

        $client = PlatformFactory::create($this->biz);

        $file = $client->uploadImg($media_id, 'display-wall', 1, $userId);

        if (empty($file)) {
            throw $this->createInvalidArgumentException('图片上传出错');
        }

        $file = $this->getFileService()->createFile('weixin', $file);

        if (empty($result)) {
            $data = array(
                'activityId' => $task['activityId'],
                'courseId' => $task['courseId'],
                'courseTaskId' => $taskId,
                'groupId' => $groupMember['groupId'],
                'userId' => $userId,
            );
            $result = $this->createResult($data);

            $content = array(
                'resultId' => $result['id'],
                'userId' => $userId,
                'uri' => $file['uri'],
            );
            $content = $this->createContent($content);
        } else {
            $content = $this->getLastContentByResultId($result['id']);

            $content = $this->updateContent($content['id'], array(
                'uri' => $file['uri'],
                'userId' => $userId,
            ));
        }

        return $content;
    }

    protected function isOpenWorker()
    {
        $magic = $this->createService('System:SettingService')->get('magic');

        if (isset($magic['open_worker']) && $magic['open_worker']) {
            return true;
        }

        return false;
    }

    protected function getResultDao()
    {
        return $this->createDao('CustomBundle:DisplayWall:ResultDao');
    }

    protected function getContentDao()
    {
        return $this->createDao('CustomBundle:DisplayWall:ContentDao');
    }

    protected function getPostDao()
    {
        return $this->createDao('CustomBundle:DisplayWall:PostDao');
    }

    protected function getLikeDao()
    {
        return $this->createDao('CustomBundle:DisplayWall:LikeDao');
    }

    protected function getUserService()
    {
        return $this->createService('User:UserService');
    }

    protected function getCourseMemberService()
    {
        return $this->createService('CustomBundle:Course:MemberService');
    }

    protected function getTaskGroupService()
    {
        return $this->createService('CustomBundle:TaskGroup:TaskGroupService');
    }

    protected function getActivityService()
    {
        return $this->createService('Activity:ActivityService');
    }

    protected function getFileService()
    {
        return $this->createService('CustomBundle:File:FileService');
    }

    protected function getTaskService()
    {
        return $this->createService('CustomBundle:Task:TaskService');
    }
}
