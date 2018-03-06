import * as types from '../../mutation-types';
import Vue from 'vue';
import api from '@/assets/js/api';

export const mutations = {
  [types.ROLLCALL_CLEAR](state) {
    state.rollcall.stuData = [];
    state.rollcall.isRollcall = false;
    state.rollcall.isDialogShow = false;
    state.rollcall.currentReviewId = null;
    state.rollcall.socre = 0;
    state.rollcall.canRand = true;
  },
  [types.ROLLCALL_STUDENT](state, data) {
    state.rollcall.stuData = data.students;
    state.rollcall.canRand = data.canRand;
  },
  [types.ROLLCALL_STATUS](state, isRollcall) {
    state.rollcall.isRollcall = isRollcall
  },
  [types.ROLLCALL_TASK_STATUS](state, status) {
    state.rollcall.status = status
  },
  [types.ROLLCALL_RAND](state, data) {
    state.rollcall.stuData.unshift(data.student);
    state.rollcall.canRand = data.canRand;
  },
  [types.SET_ROLLCALL_REVIEW_DIALOG](state, isDialogShow) {
    state.rollcall.isDialogShow = isDialogShow;
  },
  [types.SET_ROLLCALL_REVIEW_CURRENT_ID](state, currentReviewId) {
    state.rollcall.currentReviewId = currentReviewId;
  },
  [types.ROLLCALL_REVIEW](state, score) {
    state.rollcall.stuData.map(item => {
      if (item.resultId === state.rollcall.currentReviewId) {
        item.score = score;
      }
    })
  },
};

export const actions = {
  [types.ROLLCALL_CLEAR]({ commit }) {
    commit(types.ROLLCALL_CLEAR);
  },
  [types.ROLLCALL_STUDENT]({ commit }, taskId) {
    return Vue.http.get(api.rollcall.students(), {
      params: {
        taskId
      }
    }).then((res) => {
      commit(types.ROLLCALL_STUDENT, res.data);
    })
  },
  [types.ROLLCALL_STATUS]({ commit }, taskId) {
    return Vue.http.get(api.rollcall.status(), {
      params: {taskId}
    }).then((res) => {
      if (res.data) { commit(types.ROLLCALL_STATUS, true);  }
      return res;
    })
  },
  [types.ROLLCALL_STATUS_OFF]({ commit }) {
    commit(types.ROLLCALL_STATUS, false);
  },
  [types.ROLLCALL_RAND]({ commit }, { taskId, courseId }) {
    return Vue.http.get(api.rollcall.rand(), {
      params: {
        taskId,
        courseId
      }
    })
    //   .then((res) => {
    //   if(!res.data.message) {
    //     setTimeout(() => {
    //       commit(types.ROLLCALL_RAND, res.data);
    //     }, 3000);
    //   } else {
    //     commit(types.ROLLCALL_TASK_STATUS, false);
    //   }
    //   return res;
    // });
  },
  [types.SET_ROLLCALL_REVIEW_DIALOG]({ commit }, isDialogShow) {
    commit(types.SET_ROLLCALL_REVIEW_DIALOG, isDialogShow);
  },
  [types.SET_ROLLCALL_REVIEW_CURRENT_ID]({ commit }, currentReviewId) {
    commit(types.SET_ROLLCALL_REVIEW_CURRENT_ID, currentReviewId);
  },
  [types.ROLLCALL_REVIEW]({ commit }, { courseId, resultId, score, remark }) {
    return Vue.http.get(api.rollcall.remark(), {
      params: {
        courseId,
        resultId,
        score,
        remark
      }
    }).then((res) => {
      commit(types.ROLLCALL_REVIEW, res.data);
    })
  },
}

