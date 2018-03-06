<template>
  <div class="group-panel">
    <div class="weui-panel weui-panel_access">
      <div class="weui-panel__hd">回复</div>
      <div class="weui-panel__bd" v-if="replyData.length > 0">
        <div class="weui-media-box weui-media-box_appmsg group-raply_appmsg"
            v-for="(reply, index) in replyData">
          <div class="weui-media-box__hd">
            <img alt="" class="code-avatar code-avatar--lg"
                :src="host + reply.avatar">
          </div>
          <div class="weui-media-box__bd clearfix">
            <h4 class="weui-media-box__title mts">
              <span class="mrm">{{ reply.truename }}</span>
              <span class="gray text-sm">{{ reply.timeStr }}</span>
              <span class="pull-right gray text-sm">{{ index + 1 }}楼</span>
            </h4>
            <div class="reply-content" v-html="reply.content"></div>
            <div class="text-primary text-right text-md mbm">
              <span @click="replyPopup(reply.id)" class="mrs">
                <i class="cz-icon cz-icon-forum"></i> 回复
              </span>
              <span v-if="reply.childPosts.length"
                    @click="show = !show">
                <span v-show="show">收起</span>
                <span v-show="!show">展开</span>
              </span>
            </div>
            <transition name="fade">
              <div v-if="show">
                <div v-for="post in reply.childPosts">
                  <div class="weui-media-box weui-media-box_appmsg reply-post"
                      @click="replyPopup(post.id)">
                    <div class="weui-media-box__h">
                      <img :src="host + post.avatar" alt="" class="code-avatar mrs">
                    </div>
                    <div class="weui-media-box__bd">
                      <h4 class="text-md">
                        {{ post.truename }}
                        <span class="gray-light text-sm mls">{{ post.timeStr }}</span>
                      </h4>
                      <div class="text-md mts">{{ post.content }}</div>
                    </div>
                  </div>
                </div>
              </div>
            </transition>
          </div>
        </div>
        <reply></reply>
      </div>
    </div>
  </div>
</template>

<script>
import Reply from './Reply';
import { mapState, mapMutations, mapActions } from 'vuex';
import * as types from '@/vuex/mutation-types';


export default {
  components: {
    Reply
  },
  data() {
    return {
      host: this.$getCookie('host'),
      show: true,
    }
  },
  computed: {
    ...mapState({
      replyData: state => state.group.replyData,
      postId: state => state.group.postId,
      fromUserId: state => state.group.fromUserId,
    })
  },
  methods: {
   ...mapMutations([
      types.GROUP_REPLY_ISSHOW,
      types.GROUP_REPLY_POSTID,
      types.GROUP_REPLY_FROMUSERID,
    ]),
    replyPopup(postId, fromUserId) {
      this[types.GROUP_REPLY_ISSHOW](true);
      this[types.GROUP_REPLY_POSTID](postId);
      this[types.GROUP_REPLY_FROMUSERID](fromUserId);
    }
  }
}
</script>

<style lang="less">
.reply-content {
  margin: 10px 10px 10px 0;
  p,span {
    margin-bottom: 10px;
    font-size: .875rem;
    line-height: 1.6;
  }
  img {
    margin: 10px 0;
  }
  table, img {
    width: 100% !important;
  }
  ol {
    padding-left: 40px;
    li {
      padding: .1875rem;
    }
  }
}

.reply-post {
  background-color: #f9f9f9;
  margin-top: 1px;
}

.reply-post {
  background-color: #f9f9f9;
  margin-top: 1px;
}

.fade-enter-active, .fade-leave-active {
  opacity: 1;
  transition: opacity .3s;
}
.fade-enter, .fade-leave-to {
  opacity: 0;
}

.weui-media-box_appmsg.group-raply_appmsg {
  align-items: flex-start;
}

</style>