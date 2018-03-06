import Vue from 'vue';
import api from '@/assets/js/api';
import * as types from '../mutation-types';

export default {
  state: {
    // 签到详情-老师
    lessonTitle: null,
    signIn: null,
    // 签到记录-老师
    studentData: null,
    isShowSheet: false,
    currentKey: 'attend',
    checkedId: null,

    signInStatus: null
  },
  mutations: {
    [types.SIGNIN_INIT](state, payload) {
      state.signIn = payload.signIn;
      state.lessonTitle = payload.lessonTitle;
    },
    [types.SIGNIN_END](state, payload) {
      state.signIn.status = payload.status;
    },
    [types.STUDENTS_INIT](state, studentData) {
      state.studentData = studentData;
    },
    [types.SET_STUDENT_SIGNIN_STATUS](state, studentData) {
      state.studentData = studentData;
    },
    [types.SET_IS_SHOW_SHEET](state, isShowSheet) {
      state.isShowSheet = isShowSheet;
    },
    [types.SET_CURRENT_KEY](state, currentKey) {
      state.currentKey = currentKey;
    },
    [types.SET_CHECKED_ID](state, checkedId) {
      state.checkedId = checkedId;
    },
    [types.STUDENT_SIGNIN_INIT](state, signInStatus) {
      state.signInStatus = signInStatus
    },
    [types.STUDENT_SIGNIN](state, signInStatus) {
      state.signInStatus = signInStatus
    },

  },
  actions: {
    [types.SIGNIN_INIT]({ commit }, lessonId) {
      commit(types.UPDATE_LOADING_STATUS, { isLoading: true });

      return Vue.http.get(api.signIn.detail(lessonId)).then((res) => {
        commit(types.UPDATE_LOADING_STATUS, { isLoading: false });
        commit(types.SIGNIN_INIT, res.data);
      })
    },
    [types.SIGNIN_END]({ commit }, signInId) {
      return Vue.http.get(api.signIn.end(signInId)).then((res) => {
        console.log('结束签到');
        commit(types.SIGNIN_END, res.data);
      })
    },
    [types.STUDENTS_INIT]({ commit }, { lessonId, timeId }) {
      return Vue.http.get(api.signIn.result(lessonId, timeId)).then((res) => {
        commit(types.UPDATE_LOADING_STATUS, { isLoading: false });

        commit(types.STUDENTS_INIT, res.data);
      })
    },
    [types.SET_STUDENT_SIGNIN_STATUS]({ commit }, { checkedId, status }) {
      return Vue.http.get(api.signIn.setStatus(checkedId), {
        params: {
          status
        }
      }).then((res) => {
        commit(types.SET_STUDENT_SIGNIN_STATUS, res.data);
      })
    },
    [types.SET_IS_SHOW_SHEET]({ commit }, isShowSheet) {
      commit(types.SET_IS_SHOW_SHEET, isShowSheet);
    },
    [types.SET_CURRENT_KEY]({ commit }, currentKey) {
      commit(types.SET_CURRENT_KEY, currentKey);
    },
    [types.SET_CHECKED_ID]({ commit }, checkedId) {
      commit(types.SET_CHECKED_ID, checkedId);
    },
    [types.STUDENT_SIGNIN_INIT]({ commit }, { lessonId, timeId }) {
      commit(types.UPDATE_LOADING_STATUS, { isLoading: true });

      return Vue.http.get(api.signIn.status(lessonId, timeId))
      .then((res) => {
        commit(types.UPDATE_LOADING_STATUS, { isLoading: false });
        commit(types.STUDENT_SIGNIN_INIT, res.data.status);
      })
    },
    [types.STUDENT_SIGNIN]({ commit }, { lessonId, timeId, code, latitude, longitude }) {
      return Vue.http.get(api.signIn.signIn(lessonId, timeId), {
        params: {
          code,
          lat: latitude,
          lng: longitude
        }
      }).then((res) => {
        return res;
      })
    },
    [types.STUDENT_SIGNIN_STATUS]({ commit }, { lessonId, timeId }) {
      return Vue.http.get(api.signIn.status(lessonId, timeId))
      .then((res) => {
        commit(types.STUDENT_SIGNIN, res.data.status);
      })
    }
  }
}
