import * as types from '../../mutation-types';
import Vue from 'vue';
import api from '@/assets/js/api';
import qs from 'qs';

export const mutations = {
  [types.BRAIN_STORM_STATUS](state, data) {
    state.brainStorm.status = data;
  },
  [types.BRAIN_STORM_RANDOM_JOIN](state, data) {
    state.brainStorm.hasGroup = data;
  },
  [types.BRAIN_STORM_INIT](state, brainStormData) {
    state.brainStorm.status = brainStormData.status;
    state.brainStorm.hasGroup = brainStormData.hasGroup;
    state.brainStorm.groups = brainStormData.groups;
    state.brainStorm.submitWay = brainStormData.submitWay;
    state.brainStorm.isAnswer = brainStormData.isAnswer;
  },
  [types.BRAIN_STORM_CLEAR](state) {
    state.brainStorm.status = null;
    state.brainStorm.hasGroup = null;
    state.brainStorm.groups = [];
    state.brainStorm.submitWay = null;
    state.brainStorm.isAnswer = null;
  },
  [types.BRAIN_STORM_SUBMIT](state, brainStormData) {
    state.brainStorm.content = brainStormData.content;
  },
  [types.SET_BRAIN_STORM_REVIEW_DIALOG](state, isDialogShow) {
    state.brainStorm.isDialogShow = isDialogShow;
  },
  [types.SET_BRAIN_STORM_REMARK_CURRENT_ID](state, currentReviewId) {
    state.brainStorm.currentReviewId = currentReviewId;
  },
  [types.BRAIN_STORM_REMARK](state, data) {
    state.brainStorm.groups.map(item => {
      item.results.map(result => {
        if (result.id === state.brainStorm.currentReviewId) {
          result.score = data.score;
        }
      });
    })
  },
  [types.BRAIN_STORM_RESULTS](state, res) {
    if (!state.brainStorm) {
      return;
    }
    state.brainStorm.groups.map(group => {
      if (group.groupId !== res.groupId) {
        return;
      }
      if (Array.isArray(group.results) && group.results.length) {
        let results = group.results.slice(0);
        const index = results.findIndex(result => {
          return result.id === res.result.id;
        });
        if (index < 0) {
          results.push(res.brainStorm);
        } else {
          results[index] = res.brainStorm;
          // 同一组的组员间，答案可以互相覆盖(回答后，别人覆盖了你的回答，你可以再次回答)
          const userId = state.userId;
          state.brainStorm.isAnswer =  (userId === res.brainStorm.userId);
        }
        group.results = results;
        group.replyCount = results.length;
      } else {
        group.replyCount = res.brainStorm.replyCount;
        group.results = [res.brainStorm];
      }
    });
  }
};

export const actions = {
  [types.BRAIN_STORM_INIT]({commit}, taskId) {
    return Vue.http.get(api.brainStorm.result(taskId)).then((res) => {
      commit(types.BRAIN_STORM_INIT, res.data);
    })
  },
  [types.BRAIN_STORM_START]({commit}, {courseId, lessonId, taskId}) {
    return Vue.http.get(api.activity.start(courseId, lessonId, taskId));
  },
  [types.BRAIN_STORM_END]({commit}, {courseId, lessonId, taskId}) {
    return Vue.http.get(api.brainStorm.end(courseId, lessonId, taskId)).then((res) => {
      commit(types.BRAIN_STORM_STATUS, res.data.status);
    });
  },
  [types.SET_BRAIN_STORM_REMARK_CURRENT_ID]({commit}, currentReviewId) {
    commit(types.SET_BRAIN_STORM_REMARK_CURRENT_ID, currentReviewId);
  },
  [types.BRAIN_STORM_CLEAR]({commit}) {
    commit(types.BRAIN_STORM_CLEAR);
  },
  [types.BRAIN_STORM_SUBMIT]({commit}, {taskId, content}) {
    return Vue.http.post(api.brainStorm.answer(taskId),
      qs.stringify({
        content: content
      }), {
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
        },
        xsrfHeaderName: 'X-CSRF-TOKEN',
        emulateJSON: true
      }).then((res) => {
      console.log(res);
      if (!res.data.message) {
        commit(types.BRAIN_STORM_SUBMIT, res.data.status);
        return res;
      }
    })
  },
  [types.BRAIN_STORM_RANDOM_JOIN]({commit}, {taskId, groupId}) {
    return Vue.http.post(api.activity.join(taskId, groupId),
      qs.stringify({}), {
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
        },
        xsrfHeaderName: 'X-CSRF-TOKEN',
        emulateJSON: true
      }).then((res) => {
      if (!res.data.message) {
        commit(types.BRAIN_STORM_RANDOM_JOIN, true);
        return res;
      }
    })
  },
  [types.BRAIN_STORM_REMARK]({commit}, {resultId, score, remark}) {
    return Vue.http.get(api.brainStorm.remark(resultId), {
      params: {
        score,
        remark
      }
    }).then((res) => {
      if (!res.data.message) {
        commit(types.BRAIN_STORM_REMARK, res.data);
        return res;
      }
    })
  }
};
