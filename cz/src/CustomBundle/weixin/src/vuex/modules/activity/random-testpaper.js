import * as types from '../../mutation-types';
import Vue from 'vue';
import api from '@/assets/js/api';
import qs from 'qs';

export const mutations = {
  [types.RANDOM_TESTPAPER_RESULT](state, data) {
    state.testpaper.questions = data.questions;
    state.testpaper.analysis = data.analysis;
    state.randomTestpaper.result = data.result;
    state.randomTestpaper.accuracy = data.accuracy;
    state.randomTestpaper.questionIds = data.questionIds;
  },
  [types.RANDOM_TESTPAPER_REDO](state, data) {
    state.testpaper.questions = data.questions;
    state.testpaper.analysis = data.analysis;
    state.randomTestpaper.questionIds = data.questionIds;
  },
  [types.RANDOM_TESTPAPER_SUBMIT](state, data) {
    state.testpaper.questions = data.questions;
    state.randomTestpaper.result = data.result;
    state.randomTestpaper.accuracy = data.accuracy;
    state.testpaper.analysis = true;
  },
  [types.RANDOM_TESTPAPER_CLEAR](state) {
    state.randomTestpaper.result = [];
    state.randomTestpaper.questionIds = [];
  },
};

export const actions = {
  [types.RANDOM_TESTPAPER_RESULT]({ commit }, {taskId}) {
    return Vue.http.get(api.randomTestpaper.result(taskId)).then((res) => {
      commit(types.RANDOM_TESTPAPER_RESULT, res.data);
      return res;
    })
  },
  [types.RANDOM_TESTPAPER_CLEAR]({ commit }) {
    commit(types.RANDOM_TESTPAPER_CLEAR);
    commit(types.TESTPAPER_CLEAR);
  },
  [types.RANDOM_TESTPAPER_REDO]({ commit }, {taskId}) {
    return Vue.http.get(api.randomTestpaper.redo(taskId)).then((res) => {
      commit(types.RANDOM_TESTPAPER_REDO, res.data);
      return res;
    })
  },
  [types.RANDOM_TESTPAPER_SUBMIT]({commit}, {taskId, questionIds, data}) {
    return Vue.http.post(api.randomTestpaper.submit(taskId),
      qs.stringify({
        data: data,
        questionIds: questionIds,
        usedTime: 100
      }), {
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
      },
      xsrfHeaderName: 'X-CSRF-TOKEN',
      emulateJSON: true
    }).then((res) => {
      if (!res.data.message) {
        commit(types.RANDOM_TESTPAPER_SUBMIT, res.data);
        return res
      }
    })
  },
}

