import * as types from '../../mutation-types';
import Vue from 'vue';
import api from '@/assets/js/api';
import qs from 'qs';

export const mutations = {
  [types.PRACTICE_WORK_INIT](state, practiceWorkData) {
    state.practiceWork.result = practiceWorkData.result;
    state.practiceWork.data = practiceWorkData.practiceWork;
    state.practiceWork.file = practiceWorkData.file;
    state.practiceWork.pictureUrl = practiceWorkData.pictureUrl;
    state.practiceWork.status = practiceWorkData.status;
  },
  [types.PRACTICE_WORK_UPLOAD](state,data){
    state.practiceWork.pictureUrl = data.url;
  },
  [types.PRACTICE_WORK_CLEAR](state) {
    state.practiceWork.result = [];
    state.practiceWork.data = {};
    state.practiceWork.file = [];
    state.practiceWork.pictureUrl = '';
    state.practiceWork.status = null;
  },
};

export const actions = {
  [types.PRACTICE_WORK_INIT]({commit}, taskId) {
    return Vue.http.get(api.practiceWork.result(taskId)).then((res) => {
      commit(types.PRACTICE_WORK_INIT, res.data);
    })
  },
  [types.PRACTICE_WORK_UPLOAD]({commit}, params) {
    return Vue.http.get(api.practiceWorkPictureUpload(),{params}).then((res) => {
      commit(types.PRACTICE_WORK_UPLOAD, res.data);
      return res;
    })
  },
  [types.PRACTICE_WORK_CLEAR]({commit}) {
    commit(types.PRACTICE_WORK_CLEAR);
  },
};
