import * as types from '../../mutation-types';
import Vue from 'vue';
import api from '@/assets/js/api';
import qs from 'qs';

export const mutations = {
  [types.QUESTION_NAIRE_RESULT](state, data) {
    state.questionnaire.resultStatus = data.status;
    state.questionnaire.status = data.taskStatus;
    state.questionnaire.taskStatus = data.taskStatus;
    state.questionnaire.resultId = data.resultId;
    state.questionnaire.questions = data.questions;
    state.questionnaire.questionResults = data.questionResults;
    state.questionnaire.memberNum = data.memberNum;
    state.questionnaire.actualNum = data.actualNum;
  },
  [types.QUESTION_NAIRE_CLEAR](state) {
    state.questionnaire.taskStatus = null;
    state.questionnaire.status = null;
    state.questionnaire.resultStatus = null;
    state.questionnaire.questions = null;
    state.questionnaire.resultId = null;
    state.questionnaire.questionResults = null;
    state.questionnaire.result = null;
    state.questionnaire.memberNum = null;
    state.questionnaire.actualNum = null;
  },
  [types.QUESTION_NAIRE_SUBMIT](state, data) {
    state.questionnaire.resultStatus = data.status;
  }
};

export const actions = {
  [types.QUESTION_NAIRE_RESULT]({commit}, {taskId, activityId}) {
    return Vue.http.get(api.questionNaire.result(taskId, activityId)).then((res) => {
      console.log(res);
      if (!res.data.message) {
        commit(types.QUESTION_NAIRE_RESULT, res.data)
        return res
      }
    })
  },
  [types.QUESTION_NAIRE_CLEAR]({commit}) {
    commit(types.QUESTION_NAIRE_CLEAR);
  },
  [types.QUESTION_NAIRE_SUBMIT]({commit}, {resultId, content}) {
    return Vue.http.post(api.questionNaire.submit(resultId),
      qs.stringify({
        content: content
      }), {
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
      },
      xsrfHeaderName: 'X-CSRF-TOKEN',
      emulateJSON: true
    }).then((res) => {
      if (!res.data.message) {
        commit(types.QUESTION_NAIRE_SUBMIT, res.data);
        return res
      }
      return res;
    })
  }
}