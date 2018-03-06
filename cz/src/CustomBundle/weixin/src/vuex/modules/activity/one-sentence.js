import * as types from '../../mutation-types';
import Vue from 'vue';
import api from '@/assets/js/api';
import qs from 'qs';
import { postHttpConfig } from '@/assets/js/config/publicConfig';

export const mutations = {
  [types.ONE_SENTENCE_STATUS](state, data) {
    state.oneSentence.status = data;
  },
  [types.ONE_SENTENCE_INIT](state, oneSentenceData) {
    state.oneSentence.status = oneSentenceData.status;
    state.oneSentence.isGrouped = oneSentenceData.isGrouped;
    state.oneSentence.results = oneSentenceData.results;
    state.oneSentence.isAnswer = oneSentenceData.isAnswer;
    state.oneSentence.content = oneSentenceData.answer.content;
    state.oneSentence.resultId = oneSentenceData.answer.resultId;
  },
  [types.ONE_SENTENCE_CLEAR](state) {
    state.oneSentence.status = null;
    state.oneSentence.isGrouped = null;
    state.oneSentence.results = [];
    state.oneSentence.isAnswer = null;
  },
  [types.ONE_SENTENCE_SUBMIT](state, oneSentenceData) {
    let onSentence =  state.oneSentence;
    onSentence.resultId = oneSentenceData.result.id;
    onSentence.content = oneSentenceData.result.content;
    onSentence.score = oneSentenceData.score;
    onSentence.isAnswer = true;
  }
};

export const actions = {
  [types.ONE_SENTENCE_START]({ commit }, { courseId, lessonId, taskId }) {
    return Vue.http.get(api.activity.start(courseId, lessonId, taskId)).then((res) => {
      commit(types.ONE_SENTENCE_STATUS, res.data.status);
    });
  },
  [types.ONE_SENTENCE_END]({ commit }, {taskId, activityId}) {
    return Vue.http.get(api.oneSentence.end(taskId, activityId)).then((res) => {
      commit(types.ONE_SENTENCE_STATUS, res.data.status);
    });
  },
  [types.ONE_SENTENCE_INIT]({ commit }, taskId) {
    return Vue.http.get(api.oneSentence.result(taskId)).then((res) => {
      if (!res.data.message) {
        commit(types.ONE_SENTENCE_INIT, res.data);
      }
      return res;
    });
  },
  [types.ONE_SENTENCE_CLEAR]({ commit }) {
    commit(types.ONE_SENTENCE_CLEAR);
  },
  [types.ONE_SENTENCE_SUBMIT]({ commit }, {taskId, content}) {
    const postData = qs.stringify({ content });

    return Vue.http.post(api.oneSentence.answer(taskId), postData, postHttpConfig).then((res) => {
      if (!res.data.message) {
        commit(types.ONE_SENTENCE_SUBMIT, res.data);
      }
      return res;
    })
  }
};