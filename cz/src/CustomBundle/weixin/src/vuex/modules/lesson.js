import Vue from 'vue';
import api from '@/assets/js/api';
import * as types from '../mutation-types';

export default {
  state: {
    courseData: null,
    currentReviewId: null
  },
  mutations: {
    [types.LESSON_INIT](state, courseData) {
      state.courseData = courseData;
    },
    [types.UPDATE_LESSON_IS_SHOW_PHASE](state, index) {
      state.courseData.lessons[index].isShowPhase = !state.courseData.lessons[index].isShowPhase;
    },
    [types.LESSON_REVIEW_CURRENT_ID](state, currentReviewId) {
      state.currentReviewId = currentReviewId;
    },
    [types.LESSON_REMARK](state, data) {
      state.courseData.lessons[state.currentReviewId].isEvaluation = data
    }
  },
  actions: {
    [types.LESSON_CLEAR]({ commit }) {
      commit(types.LESSON_INIT, null);
    },
    [types.LESSON_INIT]({ commit }, courseId) {
      commit(types.UPDATE_LOADING_STATUS, { isLoading: true });
      return Vue.http.get(api.course.lessons(courseId))
      .then((res) => {
        commit(types.UPDATE_LOADING_STATUS, { isLoading: false });
        commit(types.LESSON_INIT, res.data);
      });
    },
    [types.UPDATE_LESSON_IS_SHOW_PHASE]({ commit }, index) {
      commit(types.UPDATE_LESSON_IS_SHOW_PHASE, index);
    },
    [types.LESSON_REMARK]({ commit }, { courseId, lessonId, remark, score }) {
      return Vue.http.get(api.course.evaluation(courseId, lessonId), {
        params: {
          remark,
          score
        }
      }).then((res) => {
        commit(types.LESSON_REMARK, res.data);
      })
    }
  }
}
