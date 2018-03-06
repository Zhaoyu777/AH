<template>
  <div class="weui-flex wall-comment__header" v-if="contentData">
    <img :src="contentData.avatar ? host + contentData.avatar : host + avatarCover" alt="" class="code-avatar wall-comment__avatar">
    <div class="weui-flex__item">
      <div class="wall-comment__title">{{ contentData.name }}</div>
      <div class="wall-comment__thumb" :style="{backgroundImage: `url(${host}${contentData.thumb})` }" @click="previewPhoto(contentData.thumb)"></div>
    </div>
  </div>
</template>

<script>
import { mapState } from 'vuex';
import { avatarCover } from '@/assets/js/data';

export default {
  data() {
    return {
      avatarCover,
      host: this.$getCookie('host'),
    }
  },
  created() {
    this.$jssdk();
  },
  computed: {
    ...mapState({
      contentData: state => state.activity.displayWallDetail.contentData,
    })
  },
  methods: {
    previewPhoto(url) {
      this.$wechat.previewImage({
        current: this.host +  url, // 当前显示图片的http链接
        urls: [this.host + url] // 需要预览的图片http链接列表
      });
    },
  }
}
</script>

<style lang="less">
.wall-comment__header {
  margin: 0.9375rem;
  .wall-comment__avatar {
    margin-right: 0.9375rem;
  }
  .wall-comment__title {
    font-size: 1.0625rem;
    line-height: 1.875rem;
    margin-bottom: 0.9375rem;
  }
  .wall-comment__thumb {
    width: 16.25rem;
    height: 9.0625rem;
    border-radius: 0.375rem;
    background-size: 100% auto;
    background-position: center;
  }
}
</style>
