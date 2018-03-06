<?php

namespace CustomBundle\Biz\DisplayWall\Service;

interface ResultService
{
    /**
     * table activity_display_wall_result
     */
    public function createResult($result);

    public function remark($resultId, $fields);

    public function getResult($id);

    public function findResultsByActivityId($activityId);

    /**
     * table activity_display_wall_content
     */
    public function createContent($content);

    public function findContentsByUserIds($userIds);

    public function findContentsByResultIds($resultIds);

    public function getContent($contentId);

    public function getContentByResultIdAndUserId($resultId, $userId);

    /**
     * table activity_display_wall_post
     */
    public function createPost($post);

    public function findPostsByContentId($contentId);

    /**
     * table activity_display_wall_like
     */
    public function like($contentId);

    public function cancelLike($contentId);

    public function isLike($contentId);

    public function getLikeByContentIdAndUserId($contentId, $userId);

    public function findResultsByTaskId($taskId);
}
