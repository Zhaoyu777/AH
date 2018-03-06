import Vue from 'vue';
import * as types from '../mutation-types';
import { activityMenuData, courseMenuData, studentCourseMenuData, menuData } from '@/assets/js/data';

export default {
  state: {
    defaultMenu: [],
    courseMenu: [],
    activityMenu: [],
  },
  mutations: {
    [types.DEFAULT_MENU_INIT](state, data) {
      state.defaultMenu = data;
    },
    [types.COURSE_MENU_INIT](state, data) {
      state.courseMenu = data;
    },
    [types.ACTIVITY_MENU_INIT](state, data) {
      state.activityMenu = data;
    },
  },
  actions: {
    [types.DEFAULT_MENU_INIT]({ commit }) {
      commit(types.DEFAULT_MENU_INIT, menuData);
    },
    [types.COURSE_MENU_INIT]({ commit }, res) {
      let data;
      if (res.role === "teacher") {
        data = courseMenuData(res.courseId, res.role);
      }

      if (res.role === "student") {
        data = studentCourseMenuData(res.courseId);
      }

      commit(types.COURSE_MENU_INIT, data);
    },
    [types.ACTIVITY_MENU_INIT]({ commit }, res) {
      const data = activityMenuData({
        role: res.role,
        courseId: res.courseId,
        lessonId: res.lessonId,
        ingLessonId: res.ingLessonId,
        lessonStatus: res.lessonStatus,
        up: res.up,
        next: res.next,
      });

      commit(types.ACTIVITY_MENU_INIT, data);
    }
  }
}