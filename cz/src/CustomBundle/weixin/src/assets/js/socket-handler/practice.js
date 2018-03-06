import * as types from '@/vuex/mutation-types';
import * as allSocketTypes from '@/assets/js/config/socketTypes';
import store from '@/vuex/store';

const practice = {
  // 练一练-首次上传
  [allSocketTypes.CREATE_PRACTICE_RESULT](res) {
    Object.assign(res.content, {
      thumb: res.content.thumb || res.content.uri
    });
    const { index, result } = findPracticeIndex.call(this, res);
    result.truename = res.user.truename;

    store.commit(types.RESET_PRACTICE_RESULT, {index, result});
  },
  // 练一练 图片改变
  [allSocketTypes.CHANGE_PRACTICE_IMAGE](res) {
    const results = this.$store.state.activity.practice.results;
    Object.assign(res.content, {
      thumb: res.content.thumb || res.content.uri
    });
    const { index } = findPracticeIndex.call(this, res);
    const result = results[index];
    result.content.thumb = res.content.thumb || res.content.uri;

    store.commit(types.RESET_PRACTICE_RESULT, {index, result});
  },
  // 练一练图片点赞
  [allSocketTypes.LIKE_PRACTICE_CONTENT](res) {
    const results = this.$store.state.activity.practice.results;
    const userId = res.likeUserId;
    const currentUserId = this.$getCookie('userId');
    const { index, result } = findPracticeIndex.call(this, res);

    if(userId === currentUserId) {
      result.isStar = true;
    } else {
      result.isStar = results[index].isStar;
    }

    store.commit(types.PRACTICE_LIKE, {index, result});
  },
  // 练一练图片取消点赞
  [allSocketTypes.CANCEL_LIKE_PRACTICE_CONTENT](res) {
    const results = this.$store.state.activity.practice.results;
    const userId = res.likeUserId;
    const currentUserId = this.$getCookie('userId');
    const { index, result } = findPracticeIndex.call(this, res);

    if(userId === currentUserId) {
      result.isStar = false;
    } else {
      result.isStar = results[index].isStar;
    }

    store.commit(types.PRACTICE_LIKE, {index, result});
  },
  // 练一练图片评论
  [allSocketTypes.PRACTICE_POST_NUM](res) {
    const results = this.$store.state.activity.practice.results;
    const index = results.findIndex(item => {
      return item.id === res.result.id;
    });
    if(index > -1) {
      store.commit(types.PRACTICE_POST, {index, res});
    }
  },
};

function findPracticeIndex(res) {
  const results = this.$store.state.activity.practice.results;
  const index = results.findIndex(item => {
    return item.id === res.result.id;
  });

  res.result.content = res.content;
  const result = res.result;

  return {index, result};
}

export default practice;