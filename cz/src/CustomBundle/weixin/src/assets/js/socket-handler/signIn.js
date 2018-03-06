import * as types from '@/vuex/mutation-types';
import * as allSocketTypes from '@/assets/js/config/socketTypes';
import * as utils from '@/assets/js/utils';
import store from '@/vuex/store';

const signIn = {
  // 第一次签到
  [allSocketTypes.START_FIRST_SIGN_IN](res) {
    const lessonId = this.$store.state.study.courseData.lessonId;
    if (lessonId === res.signIn.lessonId) {
      store.dispatch(types.STUDENT_SIGNIN_INIT, {lessonId, timeId: 1});
      store.commit(types.SIGNIN_START, res);
    }
  },
  // 第二次签到
  [allSocketTypes.START_SECOND_SIGN_IN](res) {
    const lessonId = this.$store.state.study.courseData.lessonId;
    if (lessonId === res.signIn.lessonId) {
      store.dispatch(types.STUDENT_SIGNIN_INIT, {lessonId, timeId: 2});
      store.commit(types.SIGNIN_START, res);
    }
  },
  //取消签到
  [allSocketTypes.CANCEL_SIGN_IN](res) {
    store.commit(types.SIGNIN_CANCEL, 'cancel');
    store.commit(types.STUDENT_SIGNIN, null);

    utils.goToTask.call(this, res);
  },
  // 结束签到
  [allSocketTypes.END_SIGN_IN](res) {
    // const lessonId = this.$store.state.study.courseData.lessonId;
    // const currentActivity = this.$store.state.activity.currentActivity;
    this.$store.state.study.signInStatus = res.status;
    this.$store.state.study.signInTime = res.time;
    store.commit(types.STUDENT_SIGNIN, null);

    utils.goToTask.call(this, res);
  },
  // 学生签到
  [allSocketTypes.STUDENT_SIGNIN](res) {
    const userId = this.$getCookie('userId');
    if (userId === res.userId) {
      store.commit(types.STUDENT_SIGNIN, 'attend');
    }
  }
};

export default signIn;