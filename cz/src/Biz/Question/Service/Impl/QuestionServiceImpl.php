<?php

namespace Biz\Question\Service\Impl;

use Biz\BaseService;
use Biz\Task\Service\TaskService;
use AppBundle\Common\ArrayToolkit;
use Codeages\Biz\Framework\Event\Event;
use Biz\Question\Service\QuestionService;
use AppBundle\Common\Exception\ResourceNotFoundException;

class QuestionServiceImpl extends BaseService implements QuestionService
{
    public function get($id)
    {
        return $this->getQuestionDao()->get($id);
    }

    public function create($fields)
    {
        $argument = $fields;
        $user = $this->getCurrentuser();
        $fields['createdUserId'] = $user['id'];
        $fields['updatedUserId'] = $user['id'];

        if (isset($fields['content'])) {
            $fields['content'] = $this->purifyHtml($fields['content'], true);
        }

        $this->beginTransaction();
        try {
            $questionConfig = $this->getQuestionConfig($fields['type']);
            $media = $questionConfig->create($fields);

            if (!empty($media)) {
                $fields['metas']['mediaId'] = $media['id'];
            }

            $fields['createdTime'] = time();
            $fields['updatedTime'] = time();
            $fields = $questionConfig->filter($fields);

            if (!empty($fields['parentId'])) {
                $parentQuestion = $this->get($fields['parentId']);
                $fields['courseId'] = $parentQuestion['courseId'];
                $fields['lessonId'] = $parentQuestion['lessonId'];
            }

            $fields['target'] = empty($fields['courseSetId']) ? '' : 'course-'.$fields['courseSetId'];

            $question = $this->getQuestionDao()->create($fields);

            if ($question['parentId'] > 0) {
                $this->waveCount($question['parentId'], array('subCount' => '1'));
            }

            //$this->getLogService()->info('course', 'add_question', "新增题目(#{$question['id']})", $question);
            $this->dispatchEvent('question.create', new Event($question, array('argument' => $argument)));

            $this->commit();

            return $question;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function batchCreateQuestions($questions)
    {
        if (empty($questions)) {
            return array();
        }

        return $this->getQuestionDao()->batchCreate($questions);
    }

    public function update($id, $fields)
    {
        $question = $this->get($id);
        $argument = array('question' => $question, 'fields' => $fields);
        if (!$question) {
            throw new ResourceNotFoundException('question', $id);
        }

        $questionConfig = $this->getQuestionConfig($question['type']);
        if (!empty($question['metas']['mediaId'])) {
            $questionConfig->update($question['metas']['mediaId'], $fields);
        }

        $user = $this->getCurrentuser();
        $fields['updatedUserId'] = $user['id'];
        $fields['updatedTime'] = time();

        if (isset($fields['content'])) {
            $fields['content'] = $this->purifyHtml($fields['content'], true);
        }

        $fields = $questionConfig->filter($fields);

        if (!empty($question['parentId'])) {
            $parentQuestion = $this->get($fields['parentId']);
            $fields['courseId'] = $parentQuestion['courseId'];
            $fields['lessonId'] = $parentQuestion['lessonId'];
        }

        $question = $this->getQuestionDao()->update($id, $fields);

        $this->dispatchEvent('question.update', new Event($question, array('argument' => $argument)));

        return $question;
    }

    public function updateCopyQuestionsSubCount($parentId, $subCount)
    {
        return $this->getQuestionDao()->copyQuestionsUpdateSubCount($parentId, $subCount);
    }

    public function delete($id)
    {
        $question = $this->get($id);
        if (!$question) {
            return false;
        }

        if (!empty($question['metas']['mediaId'])) {
            $questionConfig = $this->getQuestionConfig($question['type']);
            $questionConfig->delete($question['metas']['mediaId']);
        }

        $result = $this->getQuestionDao()->delete($id);

        if ($question['parentId'] > 0) {
            $this->waveCount($question['parentId'], array('subCount' => '-1'));
        }

        if ($question['subCount'] > 0) {
            $this->deleteSubQuestions($question['id']);
        }

        $this->getLogService()->info('course', 'delete_question', "删除题目(#{$question['id']})", $question);
        $this->dispatchEvent('question.delete', new Event($question));

        return $result;
    }

    public function batchDeletes($ids)
    {
        if (!$ids) {
            return false;
        }

        foreach ($ids ?: array() as $id) {
            $this->delete($id);
        }

        return true;
    }

    public function deleteSubQuestions($parentId)
    {
        return $this->getQuestionDao()->deleteSubQuestions($parentId);
    }

    public function findQuestionsByIds(array $ids)
    {
        $questions = $this->getQuestionDao()->findQuestionsByIds($ids);

        return ArrayToolkit::index($questions, 'id');
    }

    public function findQuestionsByParentId($id)
    {
        return $this->getQuestionDao()->findQuestionsByParentId($id);
    }

    public function findQuestionsByCourseSetId($courseSetId)
    {
        return $this->getQuestionDao()->findQuestionsByCourseSetId($courseSetId);
    }

    public function findQuestionsByCopyId($copyId)
    {
        return $this->getQuestionDao()->findQuestionsByCopyId($copyId);
    }

    public function search($conditions, $sort, $start, $limit)
    {
        $conditions = $this->filterQuestionFields($conditions);
        $questions = $this->getQuestionDao()->search($conditions, $sort, $start, $limit);
        $that = $this;
        array_walk($questions, function (&$question) use ($that) {
            $question = $that->hasStemImg($question);
        });

        return $questions;
    }

    public function searchCount($conditions)
    {
        $conditions = $this->filterQuestionFields($conditions);

        return $this->getQuestionDao()->count($conditions);
    }

    public function getQuestionConfig($type)
    {
        return $this->biz["question_type.{$type}"];
    }

    public function waveCount($id, $diffs)
    {
        return $this->getQuestionDao()->wave(array($id), $diffs);
    }

    public function judgeQuestion($question, $answer)
    {
        if (!$question) {
            return array('status' => 'notFound', 'score' => 0);
        }

        if (!$answer) {
            return array('status' => 'noAnswer', 'score' => 0);
        }

        $questionConfig = $this->getQuestionConfig($question['type']);

        return $questionConfig->judge($question, $answer);
    }

    public function hasEssay($questionIds)
    {
        $count = $this->searchCount(array('ids' => $questionIds, 'type' => 'essay'));

        if ($count) {
            return true;
        }

        return false;
    }

    public function getQuestionCountGroupByTypes($conditions)
    {
        $conditions = $this->filterQuestionFields($conditions);

        return $this->getQuestionDao()->getQuestionCountGroupByTypes($conditions);
    }

    /**
     * question_favorite.
     */
    public function getFavoriteQuestion($favoriteId)
    {
        return $this->getQuestionFavoriteDao()->get($favoriteId);
    }

    public function createFavoriteQuestion($fields)
    {
        $user = $this->getCurrentUser();

        $fields['userId'] = $user['id'];
        $fields['target'] = $fields['targetType'].'-'.$fields['targetId'];
        $fields['createdTime'] = time();

        return $this->getQuestionFavoriteDao()->create($fields);
    }

    public function deleteFavoriteQuestion($id)
    {
        return $this->getQuestionFavoriteDao()->delete($id);
    }

    public function searchFavoriteQuestions($conditions, $orderBy, $start, $limit)
    {
        return $this->getQuestionFavoriteDao()->search($conditions, $orderBy, $start, $limit);
    }

    public function searchFavoriteCount($conditions)
    {
        return $this->getQuestionFavoriteDao()->count($conditions);
    }

    public function findUserFavoriteQuestions($userId)
    {
        return $this->getQuestionFavoriteDao()->findUserFavoriteQuestions($userId);
    }

    public function deleteFavoriteByQuestionId($questionId)
    {
        return $this->getQuestionFavoriteDao()->deleteFavoriteByQuestionId($questionId);
    }

    public function filterQuestionFields($conditions)
    {
        if (!empty($conditions['range']) && $conditions['range'] == 'lesson') {
            $conditions['lessonId'] = 0;
        }

        if (empty($conditions['difficulty'])) {
            unset($conditions['difficulty']);
        }

        if (!empty($conditions['keyword'])) {
            $conditions['stem'] = '%'.trim($conditions['keyword']).'%';
            unset($conditions['keyword']);
        }

        if (empty($conditions['excludeIds'])) {
            unset($conditions['excludeIds']);
        } else {
            $conditions['excludeIds'] = explode(',', $conditions['excludeIds']);
        }

        if (empty($conditions['lessonId'])) {
            unset($conditions['lessonId']);
        }

        if (empty($conditions['courseId'])) {
            unset($conditions['courseId']);
        }

        return $conditions;
    }

    public function findAttachments($questionIds)
    {
        if (empty($questionIds)) {
            return array();
        }

        $conditions = array(
            'type' => 'attachment',
            'targetTypes' => array('question.stem', 'question.analysis'),
            'targetIds' => $questionIds,
        );
        $attachments = $this->getUploadFileService()->searchUseFiles($conditions);
        array_walk($attachments, function (&$attachment) {
            $attachment['dkey'] = $attachment['targetType'].$attachment['targetId'];
        });

        return ArrayToolkit::group($attachments, 'dkey');
    }

    public function hasStemImg($question)
    {
        $question['includeImg'] = false;

        if (preg_match('/<img (.*?)>/', $question['stem'])) {
            $question['includeImg'] = true;
        }

        return $question;
    }

    protected function getQuestionDao()
    {
        return $this->createDao('Question:QuestionDao');
    }

    protected function getQuestionFavoriteDao()
    {
        return $this->createDao('Question:QuestionFavoriteDao');
    }

    protected function getUploadFileService()
    {
        return $this->createService('File:UploadFileService');
    }

    protected function getLogService()
    {
        return $this->createService('System:LogService');
    }

    /**
     * @return TaskService
     */
    protected function getTaskService()
    {
        return $this->createService('Task:TaskService');
    }
}
