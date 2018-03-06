import Vuex from 'vuex';
import Vue from 'vue';
import mutations from './mutations';
import actions from './actions';

import course from './modules/course';
import study from './modules/study';
import signIn from './modules/signIn';
import lesson from './modules/lesson';
import activity from './modules/activity';
import group from './modules/group';
import menu from './modules/menu';

import * as utils from '@/assets/js/utils';

Vue.use(Vuex);

const store = new Vuex.Store({
  // TODO: 使用role的地方改成通过state获取
  state: {
    isLoading: false,
    role: utils.getCookie('role'),
    host: utils.getCookie('host'),
    userId: utils.getCookie('userId'),
    isRefresh: false,
  },
  mutations,
  actions,
  modules: {
    course,
    study,
    signIn,
    lesson,
    activity,
    group,
    menu
  }
});

export default store;
