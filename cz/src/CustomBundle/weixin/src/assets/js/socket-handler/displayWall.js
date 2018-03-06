import * as types from '@/vuex/mutation-types';
import * as allSocketTypes from '@/assets/js/config/socketTypes';
import store from '@/vuex/store';

const displayWall = {
  // 展示墙-首次上传
  [allSocketTypes.CREATE_DISPLAY_WALL_RESULT](res) {
    const user = res.user;
    Object.assign(res.content, {
      thumb: res.content.thumb || res.content.uri
    });
    Object.assign(res.result, {
      avatar: user.avatar,
      number: user.number,
      truename: user.truename,
      content: res.content
    });
    store.commit(types.DISPLAY_WALL_RESULT, res);
  },
  // 展示墙 图片改变
  [allSocketTypes.CHANGE_DISPLAY_WALL_IMAGE](res) {
    res.content.thumb = res.content.uri;
    res.result.content = res.content;
    store.commit(types.DISPLAY_WALL_RESULT, res);
  },
  // 展示墙 点赞
  [allSocketTypes.LIKE_DISPLAY_WALL_CONTENT](res) {
    store.commit(types.DISPLAY_WALL_LIKE, res);
  },
  // 展示墙 取消点赞
  [allSocketTypes.CANCEL_LIKE_DISPLAY_WALL_CONTENT](res) {
    store.commit(types.DISPLAY_WALL_CANCEL_LIKE, res);
  },
  // 展示墙 评论
  [allSocketTypes.DISPLAY_WALL_POST_NUM](res) {
    const activityId = this.$store.state.activity.activityData.id;
    if (activityId === res.result.activityId) {
      store.commit(types.DISPLAY_WALL_POST, res);
    }
  },
};

export default displayWall;