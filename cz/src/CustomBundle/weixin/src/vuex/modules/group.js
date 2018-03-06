import * as types from '../mutation-types';
import Vue from 'vue';
import api from '@/assets/js/api';
import qs from 'qs';

export default {
  state: {
    isShow: false,
    postId: null,
    fromUserId: null,
    content: '',
    isMember: null,
    replyData: []
  },
  mutations: {
    [types.GROUP_INIT](state, payload) {
      state.replyData = payload;
    },
    [types.GROUP_REPLY_CONTENT](state, payload) {
      state.replyData.map(reply => {
        if (reply.id !== payload.postId) {
          return;
        }
        reply.childPosts = payload.childPosts
      });
    },
    [types.GROUP_REPLY_ISSHOW](state, payload) {
      state.isShow = payload;
    },
    [types.GROUP_REPLY_POSTID](state, postId) {
      state.postId = postId;
    },
    [types.GROUP_REPLY_FROMUSERID](state, fromUserId) {
      state.fromUserId = fromUserId;
    },
    [types.GROUP_ISMEMBER](state, payload) {
      state.isMember = payload;
    },
  },
  actions: {
    [types.GROUP_REPLY_CONTENT]({ commit }, { groupId, threadId, content, postId, fromUserId }) {
      return Vue.http.post(api.group.post(groupId, threadId),
        qs.stringify({
          content: content,
          postId: postId,
          fromUserId: fromUserId,
        }), {
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
        },
        xsrfHeaderName: 'X-CSRF-TOKEN',
        emulateJSON: true
      }).then((res) => {
        commit(types.GROUP_REPLY_CONTENT, res.data);
        return res;
      }).catch((res) => {
        return res.response.data.error;
      });
    }
  }
}
