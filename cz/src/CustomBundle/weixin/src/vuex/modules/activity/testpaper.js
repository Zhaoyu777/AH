import * as types from '../../mutation-types';
import Vue from 'vue';
import api from '@/assets/js/api';
import qs from 'qs';

export const mutations = {
  [types.TESTPAPER_RESULT](state, data) {
    state.testpaper.questions = data.questions;
    state.testpaper.result = data.result;
    state.testpaper.status = data.status;
    state.testpaper.accuracy = data.accuracy;
  },
  [types.TESTPAPER_CLEAR](state) {
    state.testpaper.status = null;
    state.testpaper.items = null;
    state.testpaper.analysis = false;
    state.testpaper.result = [];
    state.testpaper.statis = [];
    state.testpaper.accuracy = [];
    state.testpaper.questions = [];
  },
  [types.TESTPAPER_SUBMIT](state, data) {
    state.testpaper.result = data.result;
    state.testpaper.questions = data.questions;
    state.testpaper.accuracy = data.accuracy;
  },
  [types.TESTPAPER_START](state, data) {
    state.testpaper.result = data.result;
    state.testpaper.questions = data.questions;
  },
  [types.TESTPAPER_ANALYSIS](state) {
    state.testpaper.analysis = true;
  },
  [types.TESTPAPER_STATIS](state, data) {
    state.testpaper.analysis = true;
    state.testpaper.statis = data.statis;
  },
};

export const actions = {
  [types.TESTPAPER_RESULT]({commit}, {taskId}) {
    return Vue.http.get(api.testpaper.result(taskId)).then((res) => {
      commit(types.TESTPAPER_RESULT, res.data)
      return res;
    })
  },
  [types.TESTPAPER_CLEAR]({commit}) {
    commit(types.TESTPAPER_CLEAR);
  },
  [types.TESTPAPER_START_TASK]({commit}, {courseId, lessonId, taskId}) {
    return Vue.http.get(api.activity.start(courseId, lessonId, taskId));
  },
  [types.TESTPAPER_END_TASK]({commit}, {courseId, lessonId, taskId}) {
    return Vue.http.get(api.activity.end(courseId, lessonId, taskId));
  },
  [types.TESTPAPER_SUBMIT]({commit}, {resultId, data}) {
    return Vue.http.post(api.testpaper.submit(resultId),
      qs.stringify({
        data: data,
        usedTime: 100
      }), {
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
      },
      xsrfHeaderName: 'X-CSRF-TOKEN',
      emulateJSON: true
    }).then((res) => {
      if (!res.data.message) {
        commit(types.TESTPAPER_SUBMIT, res.data);
        return res
      }
    })
  },
  [types.TESTPAPER_START]({commit}, {taskId}) {
    return Vue.http.get(api.testpaper.start(taskId)).then((res) => {
      commit(types.TESTPAPER_START, res.data);
      return res;
    })
  },
  [types.TESTPAPER_ANALYSIS]({commit}) {
    commit(types.TESTPAPER_ANALYSIS);
  },
  [types.TESTPAPER_STATIS]({commit}, {taskId}) {
    return Vue.http.get(api.testpaper.statis(taskId)).then((res) => {
      commit(types.TESTPAPER_STATIS, res.data);
      return res;
    })
  },
}