<?php

namespace CustomBundle\Biz\Practice\Service;

interface PracticeResultService
{
    public function createContent($content);

    public function getContent($contentId);

    public function like($contentId);

    public function cancelLike($contentId);

    public function isLike($contentId);

    public function deleteLikesByContentId($contentId);

    public function getLikeByContentIdAndUserId($contentId, $userId);

    public function createPost($fields);

    public function deletePost($id);

    public function deletePostsByContendId($contentId);
}
