import * as types from '../../mutation-types';
import Vue from 'vue';
import api from '@/assets/js/api';

export const mutations = {
  [types.DISPLAY_WALL_DETAIL_INIT](state, data) {
    state.displayWallDetail.postData = data.posts;
    state.displayWallDetail.contentData = data.content;
  },
  [types.DISPLAY_WALL_DETAIL_REPLY](state, item) {
    state.displayWallDetail.placeholder = item.placeholder;
    state.displayWallDetail.replyName = item.replyName;
    state.displayWallDetail.parentId = item.parentId;
  },
  [types.DISPLAY_WALL_DETAIL_SUBMIT](state, data) {
    state.displayWallDetail.postData.push(data);
    state.displayWallDetail.replyName = null;
    state.displayWallDetail.parentId = null;
    state.displayWallDetail.placeholder = '评论';
  }
};

export const actions = {
  [types.DISPLAY_WALL_DETAIL_INIT]({ commit }, contentId) {
    return Vue.http.get(api.displayWall.content(contentId)).then((res) => {
      commit(types.DISPLAY_WALL_DETAIL_INIT, res.data);
    });
  },
  [types.DISPLAY_WALL_DETAIL_REPLY]({ commit }, {...item}) {
    commit(types.DISPLAY_WALL_DETAIL_REPLY, item);
  },
  [types.DISPLAY_WALL_DETAIL_SUBMIT]({ commit }, {contentId, content, parentId}) {
    return Vue.http.get(api.displayWall.post(contentId), {
      params: {
        content,
        parentId,
      }
    }).then((res) => {
      commit(types.DISPLAY_WALL_DETAIL_SUBMIT, res.data);
      return res;
    })
  }
};
