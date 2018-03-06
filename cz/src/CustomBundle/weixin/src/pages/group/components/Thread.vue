<template>
  </scroller>
    <div class="weui-panel__bd">
      <div v-for="topic in threadData">
        <router-link
          :to="{ name: 'threadDetail', params: { groupId: topic.groupId, threadId: topic.id} }"
          class="weui-media-box weui-media-box_appmsg">
          <div class="weui-media-box__hd">
            <div class="weui-media-box__reply">
              <div class="reply-num">{{ topic.postNum }}</div>回复
            </div>
          </div>
          <div class="weui-media-box__bd">
            <h4 class="weui-media-box__title">
              {{ topic.title }}
            </h4>
            <p class="weui-media-box__desc text-sm">
              <img :src="host + topic.avatar" class="code-avatar code-avatar--xxs mrs">
              <span class="gray">{{ topic.nickname }}</span> · {{ topic.timeStr }} · {{ topic.groupTitle }}
            </p>
          </div>
        </router-link>
      </div>
    </div>
  </scroller>
</template>

<script>
import api from '@/assets/js/api';

export default {
  props: ['threadData'],
  data() {
    return {
      host: this.$getCookie('host'),
    }
  }
}
</script>

<style lang="less">
  .weui-panel__hd {
    font-size: .875rem;
    padding: .9375rem;
    color: #414141;
  }
  .weui-panel__bd {
    background-color: #fff;
  }
  .weui-media-box_appmsg {
    padding: 10px;
    .weui-media-box__hd {
      width: 50px;
      height: 50px;
      border-radius: .3125rem;
      line-height: 1.45;
      .weui-media-box__thumb {
        border-radius: .3125rem;
        width: 100%;
        height: 100%;
      }
      .weui-media-box__reply {
        width: 100%;
        height: 100%;
        border-radius: .3125rem;
        background-color: #d7e6f8;
        color: #fff;
        line-height: 1.6;
      }
      .reply-num {
        padding-top: .1875rem;
        font-size: 1.0625rem;
      }
    }
  }
  .weui-media-box__title {
    // font-size: .875rem;
    margin-bottom: .5rem;
    color: #414141;
  }
  .weui-media-box__desc {
    // color: #ccc;
    font-size: .875rem;
    -webkit-line-clamp: 1;
    .code-avatar {
      vertical-align: bottom;
    }
  }
</style>