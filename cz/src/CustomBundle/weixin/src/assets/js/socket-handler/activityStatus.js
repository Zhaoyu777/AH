import * as types from '@/vuex/mutation-types';
import * as allActivityTypes from '@/assets/js/config/activityTypes';
import * as allSocketTypes from '@/assets/js/config/socketTypes';
import store from '@/vuex/store';
import * as utils from '@/assets/js/utils';

const COUNT_DOWN = 4;

const activity = {
  // 当前活动
  [allSocketTypes.CURRENT_TEACHING_TASK](res) {
    const currentActivity = this.$store.state.activity.currentActivity;
    const role = this.$store.state.activity.activityData.role;
    if (res.taskId === currentActivity.taskId || role === 'teacher') {
      // 学生在当前活动中，或者是老师时
      return;
    }
    utils.goToTask.call(this, res);

    store.commit(types.CURRENT_ACTIVITY, res);
  },
  // 表示：活动开始【抢答、开始答题】
  [allSocketTypes.START_TASK](res) {
    const taskType = res.task.type,
      activity = this.$store.state.activity,
      activityId = activity.activityData.id;

    if (res.task.courseId === this.$route.params.courseId) {
      let courseData = this.$store.state.study.courseData;
      courseData && (courseData.currentTask = res.currentTask);
    }

    if (activityId !== res.task.activityId) {
      return;
    }

    store.commit(types.ACTIVITY_STATUS_CHANGE, { activityType: res.task.type, status: res.status.status });

    let taskData = activity[taskType];
    if (taskType === allActivityTypes.RACE_ANSWER.key) { // 抢答
      taskData.time = COUNT_DOWN;
      const countDown = setInterval(() => {
        taskData.time = (taskData.time - 0.1).toFixed(1);
        taskData.time <= 0 && clearInterval(countDown);
      }, 100);
    } else if (taskType === allActivityTypes.DISPLAY_WALL.key) {
      store.dispatch(types.DISPLAY_WALL_INIT, res.task.id);
    } else if (taskType === allActivityTypes.BRAIN_STORM.key) {
      store.dispatch(types.BRAIN_STORM_INIT, res.task.id);
    } else if (taskType === allActivityTypes.PRACTICE_WORK.key) {
      store.dispatch(types.PRACTICE_WORK_INIT, res.task.id);
    } else if (taskType === allActivityTypes.PRACTICE.key) {
      store.dispatch(types.PRACTICE_INIT, res.task.id);
    }
  },
  // 结束活动
  [allSocketTypes.END_TASK](res) {
    const activityId = this.$store.state.activity.activityData.id;
    if (activityId === res.task.activityId) {
      store.commit(types.ACTIVITY_STATUS_CHANGE, { activityType: res.task.type, status: res.status.status });
    }
    if (res.task.type === allActivityTypes.PRACTICE_WORK.key && activityId === res.task.activityId) {
      store.dispatch(types.PRACTICE_WORK_INIT, res.task.id);
    }
  },
  // 课中活动是否可见
  [allSocketTypes.COURSE_TASK_START](res) {
    store.commit(types.COURSE_TASK_START, res);

    let menuData = {
      role: this.$store.state.activity.activityData.role,
      courseId: res.courseId,
      lessonId: res.lessonId,
      lessonStatus: res.lessonStatus,
      up: res.up,
      next: res.next,
    };
    store.commit(types.SET_CURRENT_TASK, res.currentTask);
    store.dispatch(types.ACTIVITY_MENU_INIT, menuData);
  },
};

export default activity;