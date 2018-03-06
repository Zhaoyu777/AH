import * as types from '../../mutation-types';
import Vue from 'vue';
import api from '@/assets/js/api';
import qs from 'qs';
import { postHttpConfig } from '@/assets/js/config/publicConfig';

export const mutations = {
  [types.PRACTICE_INIT](state, data) {
    state.practice = data;
  },
  [types.PRACTICE_CLEAR](state) {
    state.practice.taskStatus = '';
    state.practice.results = [];
  },
  [types.PRACTICE_LIKE](state, {index, result}) {
    const currentResult = state.practice.results[index];
    currentResult.isStar = result.isStar;
    currentResult.content.likeNum = result.content.likeNum;
  },
  [types.PRACTICE_POST](state, {index, res}) {
    const currentResult = state.practice.results[index];
    currentResult.content.postNum = res.postNum;
  },
  [types.RESET_PRACTICE_RESULT](state, {index, result}) {
    if(index > -1) {
      state.practice.results[index] = result;
    } else {
      state.practice.results.push(result);
    }
  },
  [types.SET_PRACTICE_REVIEW_CURRENT_ID](state, resultId) {
    state.practice.currentResultId = resultId;
  }
};

export const actions = {
  [types.PRACTICE_INIT]({ commit }, taskId) {
    return Vue.http.get(api.practice.result(taskId)).then(res => {
        commit(types.PRACTICE_INIT, res.data);
      });
  },
  [types.PRACTICE_LIKE]({ commit }, { contentId }) {
    return Vue.http.get(api.practice.like(contentId));
  },
  [types.PRACTICE_CANCEL_LIKE]({ commit }, { contentId }) {
    return Vue.http.get(api.practice.cancelLike(contentId));
  },
  [types.PRACTICE_POST]({ commit }, { contentId }) {
    return Vue.http.post(api.practice.post(contentId));
  },
  [types.PRACTICE_REVIEW]({ commit }, { courseId, resultId, score, remark }) {
    const postData = qs.stringify({courseId, resultId, score, remark });

    return Vue.http.post(api.practice.remark(resultId), postData, postHttpConfig);
  }
};