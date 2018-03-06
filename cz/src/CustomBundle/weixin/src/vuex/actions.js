import * as types from './mutation-types';
import Vue from 'vue';
import api from '@/assets/js/api';

const actions = {
  [types.UPDATE_LOADING_STATUS]({ commit }, { isLoading }) {
    commit(types.UPDATE_LOADING_STATUS, { isLoading });
  },
  [types.CURRENT_USER_INFO]({ commit }) {
    commit(types.UPDATE_LOADING_STATUS, { isLoading: true });

    return Vue.http.get(api.currentUserInfo()).then((res) => {
      commit(types.UPDATE_LOADING_STATUS, { isLoading: false });
      commit(types.CURRENT_USER_INFO, res.data);
      return res;
    })
  },
  [types.USER_LOGIN]({ commit }, id) {
    commit(types.UPDATE_LOADING_STATUS, { isLoading: true });

    return Vue.http.get(api.userLogin(), {
      params: {
        id
      }
    }).then((res) => {
      commit(types.USER_LOGIN);
      commit(types.UPDATE_LOADING_STATUS, { isLoading: false });
      commit(types.CURRENT_USER_INFO, res.data);

      return res;
    })
  },
  [types.HOST_NAME]({ commit }) {
    return Vue.http.get(api.hostName()).then((res) => {
      return res;
    })
  },
  [types.TASK_START]({commit}, {courseId, lessonId, taskId}) {
    return Vue.http.get(api.activity.start(courseId, lessonId, taskId));
  },
  [types.TASK_END]({commit}, {courseId, lessonId, taskId}) {
    return Vue.http.get(api.activity.end(courseId, lessonId, taskId));
  },
};

export default actions;
