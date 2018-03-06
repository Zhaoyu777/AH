<?php

namespace Biz\Task\Strategy\Impl;

use AppBundle\Common\ArrayToolkit;
use Biz\Task\Strategy\BaseStrategy;
use Biz\Task\Strategy\CourseStrategy;
use Biz\Task\Visitor\CourseStrategyVisitorInterface;
use Codeages\Biz\Framework\Service\Exception\NotFoundException;
use Codeages\Biz\Framework\Service\Exception\InvalidArgumentException;

class DefaultStrategy extends BaseStrategy implements CourseStrategy
{
    public function accept(CourseStrategyVisitorInterface $visitor)
    {
        $method = 'visit'.substr(strrchr(__CLASS__, '\\'), 1);

        return $visitor->$method($this);
    }

    public function canLearnTask($task)
    {
        return true;
    }

    public function getTasksTemplate()
    {
        return 'course-manage/tasks/default-tasks.html.twig';
    }

    public function getTaskItemTemplate()
    {
        return 'task-manage/item/default-list-item.html.twig';
    }

    public function createTask($field)
    {
        $this->validateTaskMode($field);

        if ($field['mode'] == 'lesson') {
            // 创建课时
            return $this->_createLesson($field);
        } else {
            // 创建课时中的环节
            return $this->_createLessonLink($field);
        }
    }

    public function updateTask($id, $fields)
    {
        $this->validateTaskMode($fields);
        $task = parent::updateTask($id, $fields);

        if ($task['mode'] == 'lesson') {
            $this->getCourseService()->updateChapter(
                $task['courseId'],
                $task['categoryId'],
                array('title' => $task['title'])
            );
        }

        return $task;
    }

    public function deleteTask($task)
    {
        if (empty($task)) {
            return;
        }
        try {
            $this->biz['db']->beginTransaction();
            $allTasks = array();
            if ($task['mode'] == 'lesson') {
                $allTasks = $this->getTaskDao()->findByCourseIdAndCategoryId(
                    $task['courseId'],
                    $task['categoryId']
                );
            } else {
                array_push($allTasks, $task);
            }
            foreach ($allTasks as $_task) {
                $this->getTaskDao()->delete($_task['id']);
                $this->getTaskResultService()->deleteUserTaskResultByTaskId($_task['id']);
                $this->getActivityService()->deleteActivity($_task['activityId']);
            }

            $this->biz['db']->commit();
        } catch (\Exception $e) {
            $this->biz['db']->rollback();
            throw $e;
        }
    }

    protected function validateTaskMode($field)
    {
        if (empty($field['mode']) || !in_array(
                $field['mode'],
                array('preparation', 'lesson', 'exercise', 'homework', 'extraClass')
            )
        ) {
            throw new InvalidArgumentException('task mode  Invalid');
        }
    }

    public function prepareCourseItems($courseId, $tasks, $limitNum)
    {
        if ($limitNum) {
            $tasks = array_slice($tasks, 0, $limitNum);
        }
        $tasks = $this->sortTasks($tasks);

        $items = array();
        $chapters = $this->getChapterDao()->findChaptersByCourseId($courseId);
        foreach ($chapters as $chapter) {
            $chapter['itemType'] = 'chapter';
            $items["chapter-{$chapter['id']}"] = $chapter;
        }

        uasort(
            $items,
            function ($item1, $item2) {
                return $item1['seq'] > $item2['seq'];
            }
        );

        $taskCount = 1;
        foreach ($items as $key => $item) {
            if ($limitNum && $taskCount > $limitNum) {
                unset($items[$key]);
            }
            if ($item['type'] !== 'lesson') {
                continue;
            }

            if (!empty($tasks[$item['id']])) {
                $items[$key]['tasks'] = $tasks[$item['id']];
                $taskCount += count($tasks[$item['id']]);
            } else {
                unset($items[$key]);
            }
        }

        return $items;
    }

    protected function sortTasks($tasks)
    {
        $tasks = ArrayToolkit::group($tasks, 'categoryId');
        $modes = array(
            'preparation' => 0,
            'lesson' => 1,
            'exercise' => 2,
            'homework' => 3,
            'extraClass' => 4,
        );

        foreach ($tasks as $key => $taskGroups) {
            uasort(
                $taskGroups,
                function ($item1, $item2) use ($modes) {
                    return $modes[$item1['mode']] > $modes[$item2['mode']];
                }
            );

            $tasks[$key] = $taskGroups;
        }

        return $tasks;
    }

    //发布课时中一组任务
    public function publishTask($task)
    {
        $tasks = $this->getTaskDao()->findByChapterId($task['categoryId']);
        foreach ($tasks as $task) {
            $this->getTaskDao()->update($task['id'], array('status' => 'published'));
        }
        $task['status'] = 'published';

        return $task;
    }

    //取消发布课时中一组任务
    public function unpublishTask($task)
    {
        $tasks = $this->getTaskDao()->findByChapterId($task['categoryId']);
        foreach ($tasks as $task) {
            $this->getTaskDao()->update($task['id'], array('status' => 'unpublished'));
        }
        $task['status'] = 'unpublished';

        return $task;
    }

    private function _createLesson($task)
    {
        $chapter = array(
            'courseId' => $task['fromCourseId'],
            'title' => $task['title'],
            'type' => 'lesson',
        );
        $chapter = $this->getCourseService()->createChapter($chapter);
        $task['categoryId'] = $chapter['id'];

        return parent::createTask($task);
    }

    private function _createLessonLink($task)
    {
        $lessonTask = $this->getTaskDao()->getByChapterIdAndMode($task['categoryId'], 'lesson');

        if (empty($lessonTask)) {
            throw new NotFoundException('lesson task is not found');
        }

        $task = parent::createTask($task);
        if ($lessonTask['status'] == 'published') {
            $this->getTaskService()->publishTask($task['id']);
        }

        return $this->getTaskService()->getTask($task['id']);
    }

    protected function getTaskSeq($taskMode, $chapterSeq)
    {
        $taskModes = array('preparation' => 1, 'lesson' => 2, 'exercise' => 3, 'homework' => 4, 'extraClass' => 5);
        if (!array_key_exists($taskMode, $taskModes)) {
            throw new InvalidArgumentException('task mode is invalida');
        }

        return $chapterSeq + $taskModes[$taskMode];
    }
}
