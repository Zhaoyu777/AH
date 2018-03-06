<template>
  <div>
    <div class="activity-body answer-body">
      <div class="activity-body__title">
        我的回答
      </div>
      <div class="activity-body__content">
        “{{ oneSentence.content }}”
      </div>
      <div v-for="item in oneSentence.results">
        <div v-if="oneSentence.isGrouped === false">
          <div v-for="member in item">
            <div class="weui-panel weui-panel_access">
              <div class="weui-panel__bd">
                <div class="weui-media-box weui-media-box_appmsg">
                  <div class="weui-media-box__hd">
                    <div class="weui-media-box__avatar">
                      <img class="code-avatar code-avatar--lg wall-photo__avatar"
                           :src="item.avatar ? host + item.avatar : host + avatarCover">
                    </div>
                  </div>
                  <div class="weui-media-box__bd">
                    <h4 class="weui-media-box__title">“{{ member.content }}”</h4>
                    <p class="weui-media-box__desc clearfix">
                      {{ member.truename }}
                      <span class="weui-media-box__date">{{ $dateFormatFn(member.createdTime) }}</span>
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div v-else>
          <div v-for="member in item.replys">
            <div class="weui-panel weui-panel_access">
              <div class="weui-panel__bd">
                <div class="weui-media-box weui-media-box_appmsg">
                  <div class="weui-media-box__hd">
                    <div class="weui-media-box__avatar">
                      <img class="code-avatar code-avatar--lg wall-photo__avatar"
                           :src="item.avatar ? host + item.avatar : host + avatarCover">
                    </div>
                  </div>
                  <div class="weui-media-box__bd">
                    <h4 class="weui-media-box__title">“{{ member.content }}”</h4>
                    <p class="weui-media-box__desc clearfix">
                      {{ member.truename }}
                      <span class="weui-media-box__date">{{ $dateFormatFn(member.createdTime) }}</span>
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { mapState, mapActions } from 'vuex';
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
      oneSentence: state => state.activity.oneSentence
    })
  }
}
</script>

<style lang="less">
  .answer-body {
    padding-left: .625rem/* 10px */;
    padding-right: .625rem/* 10px */;
  }
  .weui-media-box__title {
    white-space: normal;
    font-size: .875rem/* 14px */;
    color: #414141;
    line-height: 1.2rem;
  }
  .weui-media-box__desc {
    padding-top: .5rem/* 8px */;
    font-size: .75rem;
    color: #919191;
  }
  .wall-photo__avatar {
    vertical-align: middle;
    margin-right: .1875rem/* 3px */;
  }
  .weui-media-box__date {
    margin-left: .25rem/* 4px */;
    float: right;
    color: #ccc;
  }
  .weui-media-box_appmsg .weui-media-box__hd {
    width: 55px;
    height: 55px;
    line-height: .9em;
  }
</style>
