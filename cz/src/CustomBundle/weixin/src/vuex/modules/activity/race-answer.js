import * as types from '../../mutation-types';
import Vue from 'vue';
import api from '@/assets/js/api';

export const mutations = {
  [types.RACE_ANSWER_STATUS](state, status) {
    state.raceAnswer.status = status
  },
  [types.RACE_ANSWER_INIT](state, raceAnswerData) {
    state.raceAnswer.status = raceAnswerData.status;
    state.raceAnswer.time = raceAnswerData.time;
    state.raceAnswer.results = raceAnswerData.results;
  },
  [types.RACE_ANSWER_CLEAR](state) {
    state.raceAnswer.status = null;
    state.raceAnswer.results = [];
    state.raceAnswer.currentReviewId = null;
    state.raceAnswer.isRaced = null;
    state.raceAnswer.isDialogShow = null;
  },
  [types.SET_RACE_ANSWER_REMARK_CURRENT_ID](state, currentReviewId) {
    state.raceAnswer.currentReviewId = currentReviewId;
  },
  [types.RACE_ANSWER_REMARK](state, score) {
    state.raceAnswer.results.map(item => {
      if (item.resultId === state.raceAnswer.currentReviewId) {
        item.score = score;
      }
    })
  },
  [types.RACE_ANSWER_RESULT](state, status) {
    state.raceAnswer.isRaced = status;
  },
  [types.SET_RACE_ANSWER_REVIEW_DIALOG](state, isDialogShow) {
    state.raceAnswer.isDialogShow = isDialogShow;
  },
};

export const actions = {
  [types.RACE_ANSWER_START]({ commit }, { courseId, lessonId, taskId }) {
    return Vue.http.get(api.activity.start( courseId, lessonId, taskId )).then((res) => {
      return res;
    })
  },
  [types.RACE_ANSWER_END]({ commit }, {taskId, activityId}) {
    return Vue.http.get(api.raceAnswer.end(taskId, activityId)).then((res) => {
      commit(types.RACE_ANSWER_STATUS, res.data);
      return res;
    })
  },
  [types.RACE_ANSWER_INIT]({ commit }, taskId) {
    return Vue.http.get(api.raceAnswer.result(taskId)).then((res) => {
      commit(types.RACE_ANSWER_INIT, res.data);
      return res;
    })
  },
  [types.RACE_ANSWER_CLEAR]({ commit }){
    commit(types.RACE_ANSWER_CLEAR);
  },
  [types.SET_RACE_ANSWER_REMARK_CURRENT_ID]({ commit }, currentReviewId) {
    commit(types.SET_RACE_ANSWER_REMARK_CURRENT_ID, currentReviewId);
  },
  [types.RACE_ANSWER_REMARK]({ commit }, {courseId, resultId, score, remark}) {
    return Vue.http.get(api.raceAnswer.remark(courseId, resultId), {
      params: {
        courseId,
        resultId,
        score,
        remark
      }
    }).then((res) => {
      console.log(res);
      if (!res.data.message) {
        commit(types.RACE_ANSWER_REMARK, res.data);
      }
      return res;
    })
  },
  [types.RACE_ANSWER_RESULT]({ commit }, {courseId, taskId, activityId}) {
    return Vue.http.get(api.raceAnswer.race(courseId, taskId, activityId)).then((res) => {
      if (!res.data.message) {
        commit(types.RACE_ANSWER_RESULT, res.data);
      }
      return res;
    })
  },
}

