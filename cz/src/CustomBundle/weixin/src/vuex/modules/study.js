import Vue from 'vue';
import api from '@/assets/js/api';
import * as types from '../mutation-types';

// 这个地方的数据结构有点混乱
export default {
  state: {
    tabIndex: 1,
    courseData: null,
    lessonId: null,
    signInTime: null,
    signInStatus: null,
    lessonStatus: null,
    next: null,
    currentTask: [],
    role: null
  },
  mutations: {
    [types.STUDY_CLEAR](state, data) {
      state.courseData = null;
      state.tabIndex = 1;
    },
    [types.STUDY_INIT](state, courseData) {
      state.courseData = courseData;
      state.currentTask = courseData.currentTask;
      state.lessonId = courseData.lessonId;
      state.signInTime = courseData.signIn.time;
      state.signInStatus = courseData.signIn.status;
      state.lessonStatus = courseData.lessonStatus;
      state.role = courseData.role;
    },
    [types.STUDY_TAB_SWITCH](state, tabIndex) {
      state.tabIndex = tabIndex;
    },
    [types.SIGNIN_START](state, courseData) {
      state.signInStatus = courseData.signIn.status;
      state.signInTime = courseData.signIn.time;
    },
    [types.SIGNIN_CANCEL](state, payload) {
      state.signInStatus = payload;
    },
    [types.COURSE_START](state, courseData) {
      if(state.courseData) {
        state.courseData.lessonStatus = courseData.lessonStatus;
      }
      if(courseData.next) state.next = courseData.next;
    },
    [types.COURSE_END](state, status) {
      if(state.courseData) {
        state.courseData.lessonStatus = status;
        state.courseData.currentTask = null;
      }
      state.currentTask = [];
    },
    [types.COURSE_CANCEL](state, status) {
      if(state.courseData) {
        state.courseData.lessonStatus = status;
        state.courseData.currentTask = null;
      }
      state.currentTask = [];
    },
    [types.SET_CURRENT_TASK](state, task) {
      state.courseData.currentTask = task;
    },
    [types.COURSE_TASK_START](state,res) {
      if(!state.courseData) {
        return;
      }
      state.courseData.in.map(item => {
        if(item.taskId === res.taskId) {
          item.isVisible = res.isVisible;
        }
      })
    }
  },
  actions: {
    [types.STUDY_CLEAR]({commit}) {
      commit(types.STUDY_CLEAR);
    },
    [types.STUDY_INIT]({commit}, {courseId, lessonId}) {
      return Vue.http.get(api.course.study(courseId), {
      params: {
        lessonId
      }
    }).then((res) => {
        commit(types.STUDY_INIT, res.data);
      })
    },
    [types.STUDY_TAB_SWITCH]({commit}, tabIndex) {
      commit(types.STUDY_TAB_SWITCH, tabIndex);
    },
    [types.SIGNIN_START]({commit}, lessonId) {
      return Vue.http.get(api.signIn.start(lessonId));
    },
    [types.COURSE_START]({commit}, lessonId) {
      return Vue.http.get(api.course.start(), {
        params: {
          lessonId
        }
      }).then((res) => {
        if (!res.data.message) {
          commit(types.COURSE_START, res.data);
        }
        return res;
      })
    },
    [types.COURSE_END]({commit}, lessonId) {
      return Vue.http.get(api.course.end(), {
        params: {
          lessonId
        }
      }).then((res) => {
        if (!res.data.message) {
          commit(types.COURSE_END, 'teached');
        }
        return res;
      })
    },
    [types.COURSE_CANCEL]({commit}, lessonId) {
      return Vue.http.get(api.course.cancel(), {
        params: {
          lessonId
        }
      }).then((res) => {
        if (res.data === true) {
          commit(types.COURSE_CANCEL, 'created');
        }
        return res;
      })
    }
  }
}
