import * as types from '../mutation-types';
import Vue from 'vue';
import api from '@/assets/js/api';

export default {
  state: {
    info: {},
  },
  mutations: {
    [types.COURSE_INFO](state, payload) {
      Vue.set(state.info, [payload.courseId], payload);
    },
  },
  actions: {
    [types.COURSE_INFO]({ commit }, { courseId }) {
      commit(types.UPDATE_LOADING_STATUS, { isLoading: true });
      
      return Vue.http.get(api.course.info(courseId)).then((res) => {
        commit(types.UPDATE_LOADING_STATUS, { isLoading: false });
        commit(types.COURSE_INFO, res.data);
        
        return res;
      })
    }
  }
}
