import * as types from '../../mutation-types';
import Vue from 'vue';
import api from '@/assets/js/api';
import qs from 'qs';
import { postHttpConfig } from '@/assets/js/config/publicConfig';

export const mutations = {
  [types.PRACTICE_DETAIL_INIT](state, data) {
    state.practiceDetail.postData = data.posts;
    state.practiceDetail.contentData = data.content;
  },
  [types.PRACTICE_DETAIL_REPLY](state, item) {
    state.practiceDetail.placeholder = item.placeholder;
    state.practiceDetail.replyName = item.replyName;
    state.practiceDetail.parentId = item.parentId;
  },
  [types.PRACTICE_DETAIL_SUBMIT](state, data) {
    state.practiceDetail.postData.push(data);
    state.practiceDetail.replyName = null;
    state.practiceDetail.parentId = null;
    state.practiceDetail.placeholder = '评论';
  }
};

export const actions = {
  [types.PRACTICE_DETAIL_INIT]({ commit }, contentId) {
    return Vue.http.get(api.practice.content(contentId)).then((res) => {
      commit(types.PRACTICE_DETAIL_INIT, res.data);
    });
  },
  [types.PRACTICE_DETAIL_REPLY]({ commit }, {...item}) {
    commit(types.PRACTICE_DETAIL_REPLY, item);
  },
  [types.PRACTICE_DETAIL_SUBMIT]({ commit }, {contentId, content}) {
    const postData = qs.stringify({ content });

    return Vue.http.post(api.practice.post(contentId), postData, postHttpConfig).then((res) => {
      commit(types.PRACTICE_DETAIL_SUBMIT, res.data);
      return res;
    });
  }
};
