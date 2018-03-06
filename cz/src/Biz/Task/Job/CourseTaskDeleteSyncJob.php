<?php

namespace Biz\Task\Job;

use AppBundle\Common\ArrayToolkit;
use Biz\AppLoggerConstant;
use Biz\Course\Dao\CourseDao;
use Biz\System\Service\LogService;
use Biz\Task\Dao\TaskDao;
use Biz\Task\Strategy\CourseStrategy;
use Codeages\Biz\Framework\Scheduler\AbstractJob;

class CourseTaskDeleteSyncJob extends AbstractJob
{
    public function execute()
    {
        try {
            $taskId = $this->args['taskId'];
            $courseId = $this->args['courseId'];
            $copiedCourses = $this->getCourseDao()->findCoursesByParentIdAndLocked($courseId, 1);

            $copiedCourseIds = ArrayToolkit::column($copiedCourses, 'id');
            $copiedCourseMap = ArrayToolkit::index($copiedCourses, 'id');
            $copiedTasks = $this->getTaskDao()->findByCopyIdAndLockedCourseIds($taskId, $copiedCourseIds);
            foreach ($copiedTasks as $ct) {
                $this->deleteTask($ct['id'], $copiedCourseMap[$ct['courseId']]);
            }

            $this->getLogService()->info(AppLoggerConstant::COURSE, 'sync_when_task_delete', 'course.log.task.delete.sync.success_tips', array('taskId' => $taskId));
        } catch (\Exception $e) {
            $this->getLogService()->error(AppLoggerConstant::COURSE, 'sync_when_task_delete', 'course.log.task.delete.sync.fail_tips', $e->getMessage());
        }
    }

    private function deleteTask($taskId, $course)
    {
        return  $this->createCourseStrategy($course)->deleteTask($this->getTaskDao()->get($taskId));
    }

    /**
     * @param $course
     *
     * @return CourseStrategy
     */
    private function createCourseStrategy($course)
    {
        return $this->biz->offsetGet('course.strategy_context')->createStrategy($course['courseType']);
    }

    /**
     * @return CourseDao
     */
    private function getCourseDao()
    {
        return $this->biz->dao('Course:CourseDao');
    }

    /**
     * @return TaskDao
     */
    private function getTaskDao()
    {
        return $this->biz->dao('Task:TaskDao');
    }

    /**
     * @return LogService
     */
    private function getLogService()
    {
        return $this->biz->service('System:LogService');
    }
}
