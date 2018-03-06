<template>
  <div class="wall-comment-list">
    <div class="weui-flex wall-comment-item" @click="reply(item)" v-for="item in postData">
      <img :src="item.avatar ? host + item.avatar : host + avatarCover" alt="" class="code-avatar wall-comment__avatar">
      <div class="weui-flex__item">
        <div class="wall-comment__title">{{ item.name }}</div>
        <div class="wall-comment__info">
          <span v-if="item.replyName">
            回复
            <span class="text-primary">{{ item.replyName }}</span>：
          </span>
          {{ item.content }}
        </div>
      </div>
      <div class="wall-commen-date">{{ $dateFormatFn(item.date) }}</div>
    </div>
  </div>
</template>

<script>
import { mapActions, mapState } from 'vuex';
import * as types from '@/vuex/mutation-types';
import { avatarCover } from '@/assets/js/data';

export default {
  data() {
    return {
      avatarCover,
      host: this.$getCookie('host'),
    }
  },
  computed: {
    ...mapState({
      postData: state => state.activity.displayWallDetail.postData,
    })
  },
  methods: {
    ...mapActions([types.DISPLAY_WALL_DETAIL_REPLY]),
    reply(item) {
      this[types.DISPLAY_WALL_DETAIL_REPLY]({
        placeholder: `回复 ${item.name}：`,
        replyName: item.name,
        parentId: item.userId,
        postConent: null
      }).catch((response) => {
        this.$ajaxMessage(response.response.data.message);
      });

      // this.commentPlaceholder = `回复 ${item.name}：`;
      // this.replyName = item.name;
      // this.parentId = item.userId;
      // this.postConent = null;
    },
  }
}
</script>

<style lang="less">
.wall-comment-list {
  position: relative;
  margin: 1.875rem 0.625rem;
  background: #f3f3f7;
  border-radius: 0.3125rem;
  &:after {
    bottom: 100%;
    left: 1.25rem;
    border: solid transparent;
    content: " ";
    height: 0;
    width: 0;
    position: absolute;
    pointer-events: none;
    border-color: rgba(243, 243, 247, 0);
    border-bottom-color: #f3f3f7;
    border-width: 0.3125rem;
    margin-left: -0.3125rem;
  }
}

.wall-comment-item {
  position: relative;
  padding: 1.125rem 0.9375rem;
  + .wall-comment-item {
    border-top: 0.0625rem solid #e5e5e5;
  }
  .wall-comment__avatar {
    margin-right: 0.625rem;
  }
  .wall-comment__title {
    font-size: 0.875rem;
    color: #4993e9;
    margin-bottom: 0.3125rem;
    line-height:1.5;
  }
  .wall-comment__info {
    word-break: break-all;
    font-size: 0.875rem;
  }
  .wall-commen-date {
    position: absolute;
    top: 1.125rem;
    right: 0.9375rem;
    color: #919191;
  }
}
</style>
