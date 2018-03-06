import * as types from '@/vuex/mutation-types';
import * as allSocketTypes from '@/assets/js/config/socketTypes';
import store from '@/vuex/store';

const lessonStatus = {
  // 开始上课
  [allSocketTypes.START_LESSON]() {
    store.commit(types.COURSE_START, { lessonStatus: 'teaching' });
  },
  // 下课
  [allSocketTypes.END_LESSON]() {
    const currentActivity = this.$store.state.activity.currentActivity;
    store.commit(types.COURSE_END, 'teached');
    this.$router.replace({name: 'after-class',
      params: { courseId: currentActivity.courseId, lessonId: currentActivity.lessonId }});
  },
  // 取消上课
  [allSocketTypes.CANCEL_LESSON]() {
    store.commit(types.COURSE_CANCEL, 'created');

    const roleCookie = this.$getCookie('role');
    const role = this.$store.state.activity.activityData.role || '';
    if (role !== 'student') {
      return;
    }

    if (roleCookie === 'teacher') {
      this.$router.replace({ name: 'myAttendCourseList' });
    } else if(roleCookie === 'student') {
      this.$router.replace({ name: 'courseList' });
    }
  },
};

export default lessonStatus;